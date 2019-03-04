<?php

namespace App\Controller\Admin\Model;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatorInterface;

class CategoryController extends BaseAdminController
{

    /**
     *
     * @var TranslatorInterface 
     */
    private $translator;

    /**
     * 
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
//        parent::__construct();
        $this->translator = $translator;
    }

    public function createCategoryEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);

        $types = $this->getDoctrine()->getRepository(Category::class)->findAll();

        $formBuilder->add(
                'category', EntityType::class, [
            'class' => Category::class,
            'choice_label' =>function ($category) {
                return $category->getName();}
                ]
        );
        die(var_dump($formBuilder));

        return $formBuilder;
    }

    private function translateValues($values)
    {
        $clearValues = [];
        if ($values && count($values))
        {
            foreach ($values as $key => $value)
            {
                $clearValues[$this->translator->trans($key)] = $value;
            }
        }
        return $clearValues;
    }

}
