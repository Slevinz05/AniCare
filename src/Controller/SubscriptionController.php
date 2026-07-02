<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SubscriptionController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $stripeSecretKey,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        private readonly string $webhookSecret,
        #[Autowire('%env(STRIPE_PRICE_ID)%')]
        private readonly string $priceId,
        #[Autowire('%env(bool:STRIPE_ENABLED)%')]
        private readonly bool $stripeEnabled,
    ) {
    }

    #[Route('/abonnement', name: 'app_subscription', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        if (!$this->stripeEnabled) {
            return $this->render('subscription/disabled.html.twig');
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('subscription/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/abonnement/checkout', name: 'app_subscription_checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->stripeEnabled) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('subscription_checkout', $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_subscription');
        }

        /** @var User $user */
        $user = $this->getUser();

        Stripe::setApiKey($this->stripeSecretKey);

        if (!$user->getStripeCustomerId()) {
            $customer = Customer::create([
                'email' => $user->getEmail(),
                'metadata' => ['user_id' => $user->getId()],
            ]);
            $user->setStripeCustomerId($customer->id);
            $em->flush();
        }

        $session = StripeSession::create([
            'customer' => $user->getStripeCustomerId(),
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $this->priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('app_subscription', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url);
    }

    #[Route('/abonnement/succes', name: 'app_subscription_success', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function success(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');

        return $this->render('subscription/success.html.twig', [
            'session_id' => $sessionId,
        ]);
    }

    #[Route('/abonnement/portail', name: 'app_subscription_portal', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function portal(Request $request): Response
    {
        if (!$this->stripeEnabled) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('subscription_portal', $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_subscription');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getStripeCustomerId()) {
            $this->addFlash('warning', 'Aucun abonnement trouvé.');

            return $this->redirectToRoute('app_subscription');
        }

        Stripe::setApiKey($this->stripeSecretKey);

        $session = PortalSession::create([
            'customer' => $user->getStripeCustomerId(),
            'return_url' => $this->generateUrl('app_subscription', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url);
    }

    #[Route('/webhook/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): Response
    {
        Stripe::setApiKey($this->stripeSecretKey);

        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        } catch (\Exception) {
            return new Response('Invalid signature', 400);
        }

        $userRepository = $em->getRepository(User::class);

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $user = $userRepository->findOneBy(['stripeCustomerId' => $session->customer]);
                if ($user) {
                    $user->setStripeSubscriptionId($session->subscription);
                    $user->setSubscriptionStatus('active');
                    $user->setSubscriptionEndsAt(null);

                    if (!in_array('ROLE_PRO', $user->getRoles())) {
                        $roles = $user->getRoles();
                        $roles[] = 'ROLE_PRO';
                        $user->setRoles(array_unique($roles));
                    }

                    $em->flush();
                }
                break;

            case 'invoice.paid':
                $invoice = $event->data->object;
                $user = $userRepository->findOneBy(['stripeCustomerId' => $invoice->customer]);
                if ($user) {
                    $user->setSubscriptionStatus('active');
                    $user->setSubscriptionEndsAt(null);
                    $em->flush();
                }
                break;

            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $user = $userRepository->findOneBy(['stripeCustomerId' => $invoice->customer]);
                if ($user) {
                    $user->setSubscriptionStatus('past_due');
                    $em->flush();
                }
                break;

            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $user = $userRepository->findOneBy(['stripeCustomerId' => $subscription->customer]);
                if ($user) {
                    if ($subscription->cancel_at_period_end) {
                        $user->setSubscriptionStatus('canceled');
                        $user->setSubscriptionEndsAt(new \DateTimeImmutable('@' . $subscription->current_period_end));
                    } else {
                        $user->setSubscriptionStatus($subscription->status);
                        $user->setSubscriptionEndsAt(null);
                    }
                    $em->flush();
                }
                break;

            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $user = $userRepository->findOneBy(['stripeCustomerId' => $subscription->customer]);
                if ($user) {
                    $user->setSubscriptionStatus('inactive');
                    $user->setSubscriptionEndsAt(null);
                    $em->flush();
                }
                break;
        }

        return new Response('OK', 200);
    }
}
