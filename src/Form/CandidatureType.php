<?php

namespace App\Form;

use App\Entity\Candidature;
use App\Entity\Job;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CandidatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidat', TextType::class, ['label' => 'Nom & Prénom'])
            ->add('contenu', TextareaType::class, ['label' => 'Lettre de motivation'])
            ->add('cvFile', FileType::class, [
                'label' => 'CV (Document PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ])
            ->add('date', DateType::class, ['widget' => 'single_text', 'label' => 'Date'])
            ->add('job', EntityType::class, [
                'class' => Job::class,
                'choice_label' => 'title',
                'label' => 'Offre d\'emploi'
            ])
            ->add('valider', SubmitType::class, [
                'label' => 'Soumettre la candidature',
                'attr' => ['class' => 'btn btn-primary w-100 rounded-pill mt-3']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Candidature::class]);
    }
}