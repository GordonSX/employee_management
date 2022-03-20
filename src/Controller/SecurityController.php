<?php

namespace App\Controller;

use App\Entity\User;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, UserPasswordHasherInterface $passwordHasher): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        /*$em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => 'kjerzyna']);
        $user->setPassword($passwordHasher->hashPassword($user, 'Matherfucker21!'));
        $em->persist($user);
        $em->flush();*/
        /*$em = $this->getDoctrine()->getManager();
        $user = new User();

        $today = new \DateTime('now');
        $user->setUsername('cczerstwa');
        $user->setPassword($passwordHasher->hashPassword($user, 'matherfucker21'));
        $user->setEmail('cczerstwa@company.com');
        $user->setAddress('Krakowska 34/56, 33-100 Tarnów');
        $user->setFirstName('Cecylia');
        $user->setLastName('Czerstwa');
        $user->setPosition('Kierownik Regionalny');
        $user->setSupervisor('');
        $user->setTypeOfContract('Umowa o pracę na czas nieokreślony');
        $user->setDateOfBirth($today);
        $user->setDateOfEmployment($today);

        $em->persist($user);
        $em->flush();*/
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')){
            return $this->redirectToRoute('admin');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'     => $error
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route(
     *     "/{_locale}/change_password",
     *     name="change_password",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     * @noinspection NotOptimalRegularExpressionsInspection
     */
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Nazwa użytkownika',
                    'disabled'      => 'disabled'
                ],
                'label'     => 'Nazwa użytkownika',
            ])
            ->add('oldPassword', PasswordType::class, [
                'attr'      => [
                    'placeholder'   => 'Hasło',
                ],
                'label'     => 'Hasło',
            ])
            ->add('newPassword', PasswordType::class, [
                'attr'      => [
                    'placeholder'   => 'Nowe hasło',
                ],
                'label'     => 'Nowe hasło',
            ])
            ->add('confirmNewPassword', PasswordType::class, [
                'attr'      => [
                    'placeholder'   => 'Potwierdź nowe hasło',
                ],
                'label'     => 'Potwierdź nowe hasło',
            ])->getForm();

        $form->handleRequest($request);

        /** @noinspection NullPointerExceptionInspection */
        $username = $this->getUser()->getUserIdentifier();


        if (($form->isSubmitted()) && ($form->isValid())){
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmNewPassword = $form->get('confirmNewPassword')->getData();
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $username]);
            if ((isset($user)) && ($passwordHasher->isPasswordValid($user, $oldPassword))){
                if ($newPassword===$confirmNewPassword){
                    $uppercase = preg_match('@[A-Z]@', $newPassword);
                    $lowercase = preg_match('@[a-z]@', $newPassword);
                    $number    = preg_match('@[0-9]@', $newPassword);
                    $specialChars = preg_match('@[^\w]@', $newPassword);
                    if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($newPassword) < 8) {
                        $this->addFlash(
                            'warning',
                            'Hasło powinno zawierać co najmniej jedną wielką literę, jedną cyfrę, jeden znak 
                            specjalny i nie powinno być krótsze niż 8 znaków'
                        );
                    }elseif ($passwordHasher->isPasswordValid($user, $newPassword)){
                        $this->addFlash(
                            'warning',
                            'Nowe hasło nie może być takie samo jak poprzednie!'
                        );
                    }else{
                        $entityManager = $this->getDoctrine()->getManager();
                        $user->setPassword($passwordHasher->hashPassword($user,$newPassword));
                        $user->setFirstTimeLoggingIn(false);
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash(
                            'notice',
                            'Hasło zostało zmienione'
                        );
                        return $this->redirectToRoute('admin');
                    }
                }else{
                    $this->addFlash(
                        'warning',
                        'Hasła nie są identyczne! Podaj nowe hasło po czym potwierdź jego poprawność'
                    );
                }
            }else{
                $this->addFlash(
                    'warning',
                    'Hasło które podałeś jako obecnie używane różni się od hasła faktycznie używanego!'
                );
            }
        }

        return $this->render('security/change_password.html.twig',[
            'username'          => $username,
            'form'              => $form->createView()
        ]);
    }

}
