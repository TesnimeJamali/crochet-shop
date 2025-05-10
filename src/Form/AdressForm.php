<?php

namespace App\Form;

use App\Entity\Order;
use App\Form\Model\AddressData;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdressForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name',TextType::class,[
            "label"=>"First Name"
            ])
            ->add('last_name',TextType::class,[
                "label"=>"Last Name"
            ])
            ->add('email',EmailType::class,[
                "label"=>"Email",
            ])
            ->add('phone',TextType::class,[
                "label"=>"Phone Number",
            ])
            ->add('address',TextType::class,[
            ])
            ->add('governorate',ChoiceType::class,[
                "label"=>"Governorate",
                "choices"=>[
                    "Ariana" => "Ariana",
                    "Béja" => "Béja",
                    "Ben Arous" => "Ben Arous",
                    "Bizerte" => "Bizerte",
                    "Gabès" => "Gabès",
                    "Gafsa" => "Gafsa",
                    "Jendouba" => "Jendouba",
                    "Kairouan" => "Kairouan",
                    "Kasserine" => "Kasserine",
                    "Kebili" => "Kebili",
                    "Kef" => "Kef",
                    "Mahdia" => "Mahdia",
                    "Manouba" => "Manouba",
                    "Medenine" => "Medenine",
                    "Monastir" => "Monastir",
                    "Nabeul" => "Nabeul",
                    "Sfax" => "Sfax",
                    "Sidi Bouzid" => "Sidi Bouzid",
                    "Siliana" => "Siliana",
                    "Sousse" => "Sousse",
                    "Tataouine" => "Tataouine",
                    "Tozeur" => "Tozeur",
                    "Tunis" => "Tunis",
                    "Zaghouan" => "Zaghouan",

                ]
            ])
            ->add('ZIPCode',TextType::class,[
                "label"=>"Zip Code"
            ])

            ->add('next', SubmitType::class, ['label' => 'Next']);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddressData::class,
        ]);
    }
}
