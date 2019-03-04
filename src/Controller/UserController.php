<?php

/**
 * Class controller for user actions like register for e.g.
 *
 * @author estudiantest
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\Payment;
use App\Entity\Schedule;
use App\Entity\Rate;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use DateTime;
use App\Handlers\Command\SendMailCommand;
use App\Handlers\CommandHandlers\SendMailHandler;
use App\Controller\MailManager;
use SimpleBus\SymfonyBridge\Bus\CommandBus;
use FOS\UserBundle\Model\UserManagerInterface;
use OAuth2\OAuth2;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Psr\Log\LoggerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use FOS\UserBundle\Mailer\TwigSwiftMailer;
use FOS\UserBundle\Mailer\MailerInterface;
use \Google_Client;
use \Google_Service_Calendar;
use \Google_Service_Calendar_EventDateTime;

class UserController extends Controller
{

    private $commandBus;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var MailManager
     */
    private $mailManager;

    /**
     * @var SendMailHandler
     */
    private $sendMailHandler;

    /**
     *
     * @var UserManager
     */
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $userManager;
    private $oauthServer;
    private $accessTokenManager;
    private $tokenGenerator;
    private $fosMailer;

    /**
     * @param NormalizerInterface $normalizer
     * @param SendMailHandler $sendMailHandler
     */
    public function __construct(
        TokenGeneratorInterface $tokenGenerator,
        TwigSwiftMailer $mailer,
        OAuth2 $oauthServer,
        CommandBus $commandBus,
        NormalizerInterface $normalizer,
        SendMailHandler $sendMailHandler,
        UserManagerInterface $userManager,
        AccessTokenManagerInterface $accessTokenManager,
        LoggerInterface $logger

    )
    {
        $this->commandBus = $commandBus;
        $this->sendMailHandler = $sendMailHandler;
        $this->normalizer = $normalizer;
        $this->userManager = $userManager;
        $this->oauthServer = $oauthServer;
        $this->accessTokenManager = $accessTokenManager;
        $this->logger = $logger;
        $this->tokenGenerator = $tokenGenerator;
        $this->fosMailer = $mailer;
    }

    public function getAction(Request $request)
    {
        $params = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $result = array(
            'name' => $user->getName(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'id' => $user->getId()
        );
        return $result;
    }


    public function getPrivacyAction($id)
    {
        $cms = $this->getDoctrine()->getRepository(CMS::class)->find($id);

        return new JSONResponse($cms->getText());
    }


    public function registerDeviceAction(Request $request)
    {

        $params = json_decode($request->getContent(), true);
        $user = $this->getUser();
        if ($params && count($params)) {
            if (isset($params["device_token"])) {
                $user->setDeviceToken($params["device_token"]);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }
        return new JsonResponse($this->normalizer->normalize($user, 'json', ['user', 'user']));
    }

    public function meAction(Request $request)
    {
        $params = json_decode($request->getContent(), true);
        $user = $this->getUser();
        if ($params && count($params)) {
            if (isset($params["password"]) && isset($params["password_confirm"])) {
                if ($params["password"] != null && $params["password_confirm"] != null) {
                    if ($params["password"] == $params["password_confirm"]) {
                        $user->setPlainPassword($params["password"]);
                    }
                }
            }
            if (isset($params["name"])) {
                $user->setName($params["name"]);
            }
            if (isset($params["username"])) {
                $user->setUsername($params["username"]);
            }
            if (isset($params["email"])) {
                $user->setEmail($params["email"]);
            }
            if (isset($params["password"])) {
                $user->setPassword($params["password"]);
            }
            if (isset($params["telephone"])) {
                $user->setTelephone((string)$params["telephone"]);
            }
            if (isset($params["address"])) {
                $user->setAddress($params["address"]);
            }
            if (isset($params["activeNotif"])) {
                $user->setActiveNotif($params["activeNotif"]);
            }
            if (isset($params["activeStats"])) {
                $user->setActiveStats($params["activeStats"]);
            }
            if (isset($params["timeBeforeNotif"])) {
                $user->setTimeBeforeNotif(new DateTime($params["timeBeforeNotif"]));
            }
            if (isset($params["hasGoogleActive"]) && $params["hasGoogleActive"] == FALSE) {
                $user->setGoogleAccessToken(null);
            }
            if (isset($params["idFavorite"])) {
                $favorite = $this->getDoctrine()->getRepository(Center::class)->find($params["idFavorite"]);
                if ($favorite != null) {
                    $user->addFavorite($favorite);
                } else
                    throw new InvalidArgumentException("Choose a valid center");
            }
            if (isset($params["idDeleteFavorite"])) {
                $favoriteDel = $this->getDoctrine()->getRepository(Center::class)->find($params["idDeleteFavorite"]);
                if ($favoriteDel != null) {
                    $user->removeFavorite($favoriteDel);
                } else
                    throw new InvalidArgumentException("Choose a valid center");
            }
            $em = $this->getDoctrine()->getManager();

            $em->persist($user);
            $em->flush();
        }
        return $user;
    }

    public function newPaymentAction(Request $request)
    {
        $params = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $hasPayment = $this->getDoctrine()->getRepository(Payment::class)->findOneBy(['user' => $user]);
        if ($hasPayment != null) {
            //this way the id will never duplicate
            $user->setPayment(null);
            $em->remove($hasPayment);
            $em->persist($user);
            $em->flush();
        }
        $payment = new Payment();
        $payment->setCreationDate(new DateTime());
        $rate = $this->getDoctrine()->getRepository(Rate::class)->find($params["rate"]["id"]);
        if ($rate != null) {
            $payment->setRate($rate);
            $payment->setUser($user);
            $payment->setStatus($params["status"]);
            $user->setPayment($payment);
            $em->persist($payment);
            $em->persist($user);
            $em->flush();
            return new JSONResponse(
                array(
                    "paymentId" => $payment->getId()
                ));
        }


        throw new InvalidArgumentException("Invalid arguments");
    }


    public function registerAction(Request $request)
    {
        $params = json_decode($request->getContent(), true);
        if ($params && count($params) && isset($params["password"]) && isset($params["password_confirm"])) {
            //check email and username
            $email = $this->getDoctrine()->getRepository(User::class)->checkEmail($params["email"]);
            if ($email) {
                throw new InvalidArgumentException("Email in use");
            }
            //check passwords match
            if ($params["password"] !== $params["password_confirm"]) {
                throw new InvalidArgumentException("Passwords not match");
            }
            $em = $this->getDoctrine()->getManager();
            $user = new User();
            $user->setPlainPassword($params["password"]);
            $user->setUsername($params["email"]);
            $user->setEmail($params["email"]);

            if (isset($params["name"])) {
                $user->setName($params["name"]);
            }
            if (isset($params["lastname"])) {
                $user->setName($params["lastname"]);
            }
            if (isset($params["telephone"])) {
                $user->setTelephone($params["telephone"]);
            }
            if (isset($params["address"])) {
                $user->setAddress($params["address"]);
            }

            $user->setEnabled(true);
            $em->persist($user);
            $em->flush();

             /*  try {
                   $registerMailNotification = new SendMailCommand(
                           $params["email"], $params["name"], 'Te has registrado ', '', $this->renderView(
                                   // templates/emails/registration.html.twig
                                   'emails/register.html.twig', array('name' => $params["name"], 'password' => $params["password"])
                           ), []
                   );
                   //$this->sendMailHandler->handle($registerMailNotification);
                   $this->commandBus->handle($registerMailNotification);
               }
               catch (Error $e) {

               }*/


            return $user;
        }

        throw new NotFoundHttpException();
    }



    /** @var User $user */
    public function uploadImageAction(Request $request)
    {
        $uploadedFile = $request->files->get('file');
        $user = $this->getUser();
        if (file_exists($this->get('kernel')->getRootDir() . '/public/' . getEnv('APP_IMAGE_USER') . $user->getImage()) &&
            is_writable($this->get('kernel')->getRootDir() . '/public/' . getEnv('APP_IMAGE_USER') . $user->getImage())) {
            unlink($this->get('kernel')->getRootDir() . '/public/' . getEnv('APP_IMAGE_USER') . $user->getImage());
        }
        $user->setImageFile($uploadedFile);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return new JsonResponse(
            $this->normalizer->normalize(
                $user, 'json', ['user']
            )
        );
    }


    /**
     * @param Request $request
     * @return Response
     */
    public function sendResetPasswordAction(Request $request)
    {
        $userData = json_decode($request->getContent(), true);
        $url = getEnv('api_url') . '/api/reset-password/';
        $this->get('command_bus')->handle(
            new SendResetPasswordCommand($userData['email'], $url)
        );
        return new Response();
    }

    /**
     * @param $token
     * @return string
     */
    public function resetPasswordAction($token)
    {
        $ok_msg = "Se ha restablecido la contraseña, en unos instantes la recibirá por email.";
        $error_msg = "Este enlace ya ha sido usado.";
        /** @var User $user */
        $user = $this->get('doctrine')->getRepository('App:User')->findOneBy([
            'resetToken' => $token
        ]);
        if (!$user) {
            $content = $this->renderView(
                '@App/users/result.html.twig', ['title' => 'Resetear contraseña', 'message' => $error_msg]
            );
        } else {
            $this->get('command_bus')->handle(new ResetPasswordCommand($user));
            $content = $this->renderView(
                '@App/users/result.html.twig', ['title' => 'Resetear contraseña', 'message' => $ok_msg]
            );
        }
        return new Response(
            $content, 200, ['content-type' => 'text/html; charset=UTF-8']
        );
    }

    public function resetPasswordRequestAction(Request $request)

    {
        $userData = json_decode($request->getContent(), true);
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(["email" => $userData["email"]]);
        if (null === $user) {
            throw new NotFoundHttpException("User not  found");
        }
        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            throw new BadRequestHttpException('Password request alerady requested');
        }
        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */

            $user->setConfirmationToken($this->tokenGenerator->generateToken());
        }
        //$fosMailer = $this->get('fos_user.mailer.default');
        $this->fosMailer->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return new Response(Response::HTTP_OK);
    }


    public function logoutAction(Request $request)
    {

        try {
            $request->getSession()->invalidate();
            $this->get("security.token_storage")->setToken(null);
            return new Response();
        } catch (\Exception $e) {
            return new Response('', 400);
        }
    }

    public function socialLogin(Request $request)
    {
        $reqData = json_decode($request->getContent());
        $userData = FALSE;
        switch ($reqData->type) {
            case "google":
                $userData = $this->signInWithGoogle($reqData);
                break;
            case "facebook":
                $userData = $this->signInWithFacebook($reqData);
        }

        if ($userData) {
            //user authenticated
            $user = $this->userManager->findUserByEmail($userData->email);


            if (empty($user)) {
                $user = new User();
                $user->setEmail($userData->email);
                $user->setName($userData->name);
                $user->setUsername($userData->email);
                $user->setPlainPassword(md5(json_encode("response") . "s4l7--"));
                $user->setEnabled(TRUE);
                $user->setTelephone("");
                $user->setAddress("");

                if ($reqData->type == "google" && !empty($reqData->serverAuthCode)) {
                    $user->setGoogleServerAccessCode($reqData->serverAuthCode);
                }

                //profile picture
                $imageUrl = $userData->picture;

                $tmpFile = tmpfile();
                $tmpPath = stream_get_meta_data($tmpFile)['uri'];
                file_put_contents($tmpPath, file_get_contents($imageUrl));

                $fileExt = ".jpg";
                $fileName = md5(json_encode($user) . "" . time()) . $fileExt;
                $fileUploadPath = $this->get("kernel")->getRootDir() . "/../public" . getenv("APP_IMAGE_USER") . "/";
                copy($tmpPath, $fileUploadPath . $fileName);
                $user->setImage($fileName);
                //end of profile picture


                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
            } else if ($reqData->type == "google" && !empty($reqData->serverAuthCode)) {

                $user->setGoogleServerAccessCode($reqData->serverAuthCode);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
            }
            $userRequest = empty($request->request->all()) ? \json_decode($request->getContent(), true) : $request->request->all();
            $request->request->replace($userRequest);

            $request->request->add(array(
                "grant_type" => OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS,
                "username" => $userData->email,
                "client_id" => $reqData->client_id,
                "client_secret" => $reqData->client_secret
            ));
            $response = $this->oauthServer->grantAccessToken($request);
            $generatedAccessToken = json_decode($response->getContent(), TRUE)["access_token"];
            $accessTokenObject = $this->accessTokenManager->findTokenByToken($generatedAccessToken);
            $accessTokenObject->setUser($user);
            $accessTokenObject->setExpiresAt(strtotime("+1 year"));
            $this->accessTokenManager->updateToken($accessTokenObject);
            return $response;
        }
        throw new UnauthorizedHttpException("Invalid Access Token");
    }

    private function signInWithGoogle($reqData)
    {
        $gToken = $reqData->accessToken;
        $email = $reqData->email;
        $curlUrl = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=$gToken";
        $ch = curl_init($curlUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = json_decode(curl_exec($ch), TRUE);
        if ($response && isset($response ["email"]) && $response["email"] == $email) {
            $reqData->email = $response["email"];
            $reqData->name = $response["name"];
            $reqData->picture = $response["picture"];
            return $reqData;
        }
        return FALSE;
    }

    private function signInWithFacebook($reqData)
    {
        $fbToken = $reqData->accessToken;
        $profileId = $reqData->userId;
        $email = $reqData->email;
        $curlUrl = "https://graph.facebook.com/$profileId?fields=email,first_name,last_name,picture.width(400).height(400)&access_token=$fbToken";
        $ch = curl_init($curlUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = json_decode(curl_exec($ch), TRUE);
        if ($response && isset($response ["email"]) && $response["email"] == $email) {
            $reqData->email = $response["email"];
            $reqData->name = $response["first_name"] . " " . $response["last_name"];
            $reqData->picture = $response["picture"]["data"]["url"];
            return $reqData;
        }
        return FALSE;
    }

    public function unsubscribeAction()
    {
//        $this->get('command_bus')->handle(
//                new UnsubscribeUserCommand($this->getUser())
//        );
        return new Response('', 500);
    }

    public function paymentAction(Request $request)
    {
        $params = json_decode($request->getContent(), true);
        $merchantId = getEnv('merchantId');
        $acquirerBIN = getEnv('acquirerBIN');
        $terminalId = getEnv('terminalId');
        $url_ok = getEnv('url_ok');
        $url_nok = getEnv('url_nok');
        $encryption = getEnv('encryption');
        $money = getEnv('money');
        $paySupported = getEnv('paySupported');
        $language = getEnv('language');
        $exp = getEnv('exp');
        $key = getEnv('key');
        $amount = $params["importe"];

        if (!strpos($amount, '.')) {//for example, 25€
            $amount = $amount . '00';
        } else {//for example, 19,9€ or 19,95€
            $parts = explode('.', $amount);
            strlen($parts[1]) == 1 ? $parts[1] = $parts[1] . '0' : $parts[1];
            $amount = $parts[0] . $parts[1];
        }
        $amount = str_replace('.', '', $amount);
        $num_operation = $params["num_operation"];
        $stringToEncode = $key . $merchantId . $acquirerBIN . $terminalId .
            $num_operation . $amount . $money . $exp
            . $encryption . $url_ok . $url_nok;
        $sign = hash('sha256', $stringToEncode);

        return new JSONResponse(
            array(
                "sign" => $sign,
                "merchantId" => $merchantId,
                "acquirerBin" => $acquirerBIN,
                "terminalId" => $terminalId,
                "url_ok" => $url_ok,
                "url_nok" => $url_nok,
                "encryption" => $encryption,
                "money" => $money,
                "exp" => $exp,
                "amount" => $amount,
                "num_operation" => $num_operation,
                "paySupported" => $paySupported,
                "language" => $language,
                "string" => $stringToEncode

            ));
    }

    public function confirmPaymentAction(Request $request)
    {

        $body = $request->getContent();
        $params = explode('&', $body);

        foreach ($params as $param) {
            $paramSplitted = explode('=', $param);
            $paramsFormated[$paramSplitted[0]] = $paramSplitted[1];
        }
        $this->logger->info("RECEIVED POST PARAMS " . json_encode($paramsFormated));
        $merchantId = $paramsFormated['MerchantID'];
        $acquirerBIN = $paramsFormated['AcquirerBIN'];
        $terminalId = $paramsFormated['TerminalID'];
        $money = $paramsFormated['TipoMoneda'];
        $exp = $paramsFormated['Exponente'];
        $key = getEnv('key');
        $amount = $paramsFormated["Importe"];
        $num_operation = $paramsFormated["Num_operacion"];
        $keyToCompare = $paramsFormated["Firma"];
        $reference = '';
        if (isset($paramsFormated["Referencia"])) {
            $reference = $paramsFormated["Referencia"];
        }
        $stringToEncode = $key . $merchantId . $acquirerBIN . $terminalId .
            $num_operation . $amount . $money . $exp . $reference;
        $sign = hash('sha256', $stringToEncode);
        if ($keyToCompare == $sign) { //sign is correct

            $this->logger->info("Signs are correct");

            $payment = $this->getDoctrine()->getRepository(Payment::class)->find($num_operation);
            if ($payment->getRate() != null) { //payment exists and have a valid rate

                $this->logger->info("Payment has rate");
                $ratePrice = $payment->getRate()->getPrice();  //Correct format of price. 25€ must be 2500
                if (!strpos($ratePrice, '.')) {//for example, 25€
                    $ratePrice = $ratePrice . '00';
                } else {//for example, 19,9€ or 19,95€
                    $parts = explode('.', $ratePrice);
                    strlen($parts[1]) == 1 ? $parts[1] = $parts[1] . '0' : $parts[1];
                    $ratePrice = $parts[0] . $parts[1];
                }
                $ratePrice = str_replace('.', '', $ratePrice);
                if ($ratePrice == $amount) {//rate price and amount are correct

                    $this->logger->info("Amount is correct");

                    $business = $payment->getUser()->getBusiness();
                    $business->setRate($payment->getRate());
                    $oldRateExpiration = $business->getRateExpiration();
                    $oldRateExpiration = $oldRateExpiration->format('Y-m-d');
                    $today = new DateTime();
                    $today = $today->format("Y-m-d");

                    $today >= $oldRateExpiration ? $since = $today : $since = $oldRateExpiration;
                    $months = $payment->getRate()->getMonths();
                    $business->setRateExpiration(new \DateTime(date("Y-m-d", strtotime("$since +$months months"))));

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($business);
                    $em->flush();
                    return new JSONResponse(
                        array(
                            "status" => '$*$' . 'OKY$*$'
                        ));
                }
            }
        } else {
            return new JSONResponse(
                array(
                    "status" => 'Signs are not equal'
                ));
        }

        return null;

    }

    public function syncGoogleAction(Request $request)
    {
        $params = json_decode($request->getContent(), true);
        $user = $this->getUser();
        if (isset($params["serverAuthCode"])) {
            $user->setGoogleServerAccessCode($params["serverAuthCode"]);
            $googleClient = new \Google_Client();
            $googleClient->setAuthConfig(\GuzzleHttp\json_decode(file_get_contents($this->container->getParameter("kernel.project_dir") . "/client_secret.json"), true));
            $googleClient->setScopes(\Google_Service_Calendar::CALENDAR);
            $googleClient->setAccessType("offline");
            $googleClient->setRedirectUri("");
            //getting first refresh token
            $accessToken = $googleClient->fetchAccessTokenWithAuthCode($user->getGoogleServerAccessCode());
            if (isset($accessToken["access_token"])) {
                $user->setGoogleAccessToken($accessToken["access_token"]);
                $user->setGoogleRefreshToken($accessToken["refresh_token"]);
            } else {
                $this->logger->error("Error during sync " . json_encode($accessToken));
                throw new \Exception($accessToken["error"] . " : " . $accessToken["error_description"], 400);
            }
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return new Response('', 200);
    }
}
