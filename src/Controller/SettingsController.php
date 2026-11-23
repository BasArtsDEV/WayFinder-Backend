<?php

namespace App\Controller;

use App\Entity\User;
use App\EventListener\LocaleListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SettingsController extends AbstractController
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%app.enabled_locales%')] private readonly array $enabledLocales,
    ) {
    }

    #[Route('/settings', name: 'app_settings')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        return $this->render('settings/index.html.twig', [
            'user' => $this->getUser(),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    /**
     * Saves the display preferences: language (cookie) and time zone (user attribute).
     */
    #[Route('/settings/preferences', name: 'app_settings_preferences', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function preferences(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $response = $this->redirectToRoute('app_settings');

        if (!$this->isCsrfTokenValid('settings-preferences', $request->request->getString('_token'))) {
            $this->addFlash('error', 'flash.invalid_csrf');

            return $response;
        }

        $locale = $request->request->getString('locale');
        $timezone = $request->request->getString('timezone');

        if (!\in_array($locale, $this->enabledLocales, true) || !\in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $this->addFlash('error', 'settings.flash.invalid_preferences');

            return $response;
        }

        $user->setTimezone($timezone);
        $em->flush();

        $response->headers->setCookie(
            Cookie::create(LocaleListener::COOKIE_NAME, $locale, new \DateTimeImmutable('+1 year'))
        );
        $this->addFlash('success', 'settings.flash.preferences_saved');

        return $response;
    }
}
