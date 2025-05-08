<?php
// src/Form/ProductType.php
namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;  // Correct import for VichImageType

class ProductType extends AbstractType
{
public function buildForm(FormBuilderInterface $builder, array $options): void
{
$builder
->add('name')
->add('description')
->add('price')
->add('imageFile', VichImageType::class, [
'required' => false,
'allow_delete' => true,  // Allow file deletion if needed
'download_uri' => false, // We don't need the download URI for this field
'label' => 'Product Image', // Label for the file input field
]);
}

public function configureOptions(OptionsResolver $resolver): void
{
$resolver->setDefaults([
'data_class' => Product::class,  // Bind this form to the Product entity
]);
}
}
