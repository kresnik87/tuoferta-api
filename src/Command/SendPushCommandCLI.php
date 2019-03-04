<?php

namespace App\Command;

use App\Handlers\Command\SendPushCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\User;
use App\Entity\Meeting;

class SendPushCommandCLI extends ContainerAwareCommand {

    /**
     * SendTestPushCommand constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    protected function configure() {
        $this
                ->setName('Savetime:push:send')
                ->setDescription('Send push notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output) 
    {
        $container = $this->getContainer();
        $commandBus = $this->getContainer()->get('command_bus');
        $meetingRepository = $container->get('doctrine')->getRepository(Meeting::class);

        for($hour = 1; $hour<=24; $hour++)
        {
            $today = new \DateTime(date("Y-m-d H:i", strtotime("+$hour hour")));
            
            $meetings=$meetingRepository->findBy(['date' => $today, 'time' => $today]);
            foreach($meetings as $meeting){
                $user=$meeting->getUser();
                if ($user!=null && $user->getActiveNotif()){
                    $userTime=$user->getTimeBeforeNotif()->format('H');
                    if ($userTime==$hour)
                    {
                        //we send the message
                        $data=array();
                        $text = "No olvides tu cita en ".$meeting->getCenter()->getName(). " a las ".$meeting->getTime()->format("H:i");
                        $title = "Recordatorio Savetime";
                        $deviceToken=$user->getDeviceToken();
                        $sendPush = new SendPushCommand($deviceToken, $title, $text, $data);
                        $commandBus->handle($sendPush);
                    }
                }
            }
        }

    }

}
