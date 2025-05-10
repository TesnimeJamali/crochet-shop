<?php

namespace App\Form;

use App\Entity\Order;
use Doctrine\DBAL\Types\TextType;
use PharIo\Manifest\Email;
use phpDocumentor\Reflection\Types\String_;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name',TextType::class,[
                "label"=>"First Name",
                "mapped"=>false
            ])
            ->add('first_name',TextType::class,[
                "label"=>"First Name",
                "mapped"=>false
            ])
            ->add('email',EmailType::class,[
                "label"=>"Email",
                "mapped"=>false
            ])
            ->add('phone')
            ->add('paymentMethod',)
            ->add('shippingAdress')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
