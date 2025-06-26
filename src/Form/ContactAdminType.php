<?php
// src/Form/ContactAdminType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ContactAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.contact.email.label',
                'attr' => ['placeholder' => 'form.contact.email.placeholder', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'form.contact.email.not_blank']),
                    new Email(['message' => 'form.contact.email.invalid']),
                ],
            ])
            ->add('subject', ChoiceType::class, [
                'label' => 'form.contact.subject.label',
                'placeholder' => 'form.contact.subject.placeholder',
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    'form.contact.subject.choices.connection' => 'connection',
                    'form.contact.subject.choices.information' => 'information',
                    'form.contact.subject.choices.feature' => 'feature',
                    'form.contact.subject.choices.access' => 'access',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'form.contact.subject.not_blank']),
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'form.contact.priority.label',
                'placeholder' => 'form.contact.priority.placeholder',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'choices' => [
                    'form.contact.priority.choices.low' => 'Basse',
                    'form.contact.priority.choices.medium' => 'Moyenne',
                    'form.contact.priority.choices.high' => 'Haute',
                    'form.contact.priority.choices.critical' => 'Bloquant',
                ],
                'choice_attr' => ['form.contact.priority.choices.critical' => ['class' => 'text-danger']],
                'constraints' => [
                    new NotBlank(['groups' => ['classic'], 'message' => 'form.contact.priority.not_blank']),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'form.contact.message.label',
                'attr' => ['rows' => 6, 'placeholder' => 'form.contact.message.placeholder', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'form.contact.message.not_blank_detailed']),
                    new Length(['min' => 10, 'minMessage' => 'form.contact.message.min_length']),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'form.contact.lastName.label',
                'attr' => ['placeholder' => 'form.contact.lastName.placeholder', 'class' => 'form-control'],
                'required' => false,
                'constraints' => [
                    new NotBlank(['groups' => ['access'], 'message' => 'form.contact.lastName.not_blank']),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'form.contact.firstName.label',
                'attr' => ['placeholder' => 'form.contact.firstName.placeholder', 'class' => 'form-control'],
                'required' => false,
                'constraints' => [
                    new NotBlank(['groups' => ['access'], 'message' => 'form.contact.firstName.not_blank']),
                ],
            ])
            ->add('phonePrefix', ChoiceType::class, [
                'label' => 'form.contact.phonePrefix.label',
                'placeholder' => '...',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'choices' => ['FR (+33)' => '+33', 'ES (+34)' => '+34', 'DE (+49)' => '+49', 'GB (+44)' => '+44'],
                'constraints' => [
                    new NotBlank(['groups' => ['access'], 'message' => 'form.contact.phonePrefix.not_blank']),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'form.contact.phone.label',
                // === MODIFICATION ICI ===
                'attr' => ['placeholder' => '06 00 00 00 00', 'inputmode' => 'tel', 'class' => 'form-control'],
                'required' => false,
                'constraints' => [
                    new NotBlank(['groups' => ['access'], 'message' => 'form.contact.phone.not_blank']),
                    new Regex(['pattern' => '/^[\d\s]+$/', 'message' => 'form.contact.phone.invalid_format', 'groups' => ['access']])
                ],
            ])
            ->add('companyName', TextType::class, [
                'label' => 'form.contact.companyName.label',
                'attr' => ['placeholder' => 'form.contact.companyName.placeholder', 'class' => 'form-control'],
                'required' => false,
            ])
            ->add('honeypot', TextType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'honeypot-field', 'autocomplete' => 'off', 'tabindex' => '-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'messages',
            'constraints' => [new Callback(['callback' => [$this, 'validatePhoneNumber'], 'groups' => ['access']])]
        ]);
    }

    /**
     * Valide la cohérence du numéro de téléphone avec l'indicatif.
     */
    public function validatePhoneNumber(array $data, ExecutionContextInterface $context): void
    {
        $prefix = $data['phonePrefix'] ?? null;
        $phone = $data['phone'] ?? null;

        if (empty($prefix) || empty($phone)) {
            return;
        }

        $phoneDigits = preg_replace('/\s+/', '', $phone);

        if ($prefix === '+33') {
            if (str_starts_with($phoneDigits, '0')) {
                $phoneDigits = substr($phoneDigits, 1);
            }
            
            if (!preg_match('/^[679]\d{8}$/', $phoneDigits)) {
                $context->buildViolation('form.contact.phone.prefix_mismatch')
                        ->atPath('phone')
                        ->addViolation();
            }
        }
    }
}