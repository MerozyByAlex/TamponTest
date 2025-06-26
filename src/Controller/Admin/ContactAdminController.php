<?php
// src/Controller/Admin/ContactAdminController.php
namespace App\Controller\Admin;

use App\Form\ContactAdminType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

class ContactAdminController extends AbstractController
{
    #[Route('/contact', name: 'admin_contact')]
    public function index(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $validationGroups = ['Default'];

        if ($request->isMethod('POST')) {
            $submittedData = $request->request->all('contact_admin');
            $subject = $submittedData['subject'] ?? '';

            if ($subject === 'access') {
                $validationGroups[] = 'access';
            }
            elseif (in_array($subject, ['connection', 'feature'])) {
                $validationGroups[] = 'classic';
            }
        }
        
        $form = $this->createForm(ContactAdminType::class, null, [
            'validation_groups' => $validationGroups,
        ]);

        $form->handleRequest($request);

        $visible_section = null;
        if ($form->isSubmitted()) {
            $subject = $form->get('subject')->getData();
            if ($subject === 'access') {
                $visible_section = 'access';
            } elseif (in_array($subject, ['connection', 'feature'])) {
                $visible_section = 'priority';
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($form->get('honeypot')->getData())) {
                // Comportement pour le honeypot (simule un succès)
            } else {
                // Traitement normal
                $data = $form->getData();
                $emailContext = $data;
                $emailContext['translated_subject'] = $translator->trans('form.contact.subject.choices.' . $data['subject'], [], 'messages');
                
                // === LOGIQUE DE FORMATAGE DU TÉLÉPHONE AMÉLIORÉE ICI ===
                if ($data['subject'] === 'access' && isset($data['phonePrefix'], $data['phone'])) {
                    // 1. On retire les espaces existants du numéro
                    $phoneDigits = preg_replace('/\s+/', '', $data['phone']);
                    // 2. On insère un espace tous les 2 caractères
                    $formattedPhone = trim(chunk_split($phoneDigits, 2, ' '));
                    // 3. On combine l'indicatif et le numéro formaté
                    $emailContext['full_phone'] = $data['phonePrefix'] . ' ' . $formattedPhone;
                } else {
                    $emailContext['full_phone'] = null;
                }

                $adminEmail = $this->getParameter('app.admin_email');
                $email = (new Email())
                    ->from($data['email'])
                    ->to($adminEmail)
                    ->subject('Ouverture de ticket: ' . $emailContext['translated_subject'])
                    ->html($this->renderView('emails/contact/contact_admin.html.twig', ['data' => $emailContext]));
                
                $mailer->send($email);
            }
            
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $successMessage = $translator->trans('contact.flash_success', [], 'messages');
                return $this->render('admin/contact/_success.stream.html.twig', [
                    'success_message' => $successMessage,
                ], new Response('', Response::HTTP_OK, ['Content-Type' => TurboBundle::STREAM_MEDIA_TYPE]));
            }

            $this->addFlash('success', 'contact.flash_success');
            return $this->redirectToRoute('admin_contact', ['_locale' => $request->getLocale()]);
        }
        
        return $this->render('admin/contact/index.html.twig', [
            'contactForm' => $form->createView(),
            'visible_section' => $visible_section,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}