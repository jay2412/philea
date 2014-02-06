<?php

namespace Cnes\PhilaeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cnes\PhilaeBundle\Entity\Etape;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


class DefaultController extends Controller
{

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        return $this->render('PhilaeBundle:Default:index.html.twig');
    }

    /**
     * @Route("/projets/{idProjet}/")
     * @Template()
     */
    public function pageAction($idProjet)
    {
        $projet = $this->getDoctrine()->getRepository('PhilaeBundle:Projet')
            ->find($idProjet);

        if (!$projet) {
            throw $this->createNotFoundException('Aucun projet trouvé');
        }
        $lesEtapes = $this->getDoctrine()->getRepository('PhilaeBundle:Etape')->findBy(
            array('projet' => $projet, 'isValide'=> 1),
            array('avancement' => 'DESC'));
        return array('projet' => $projet, 'lesEtapes' => $lesEtapes);
    }

    /**
     * @Route("/admin/")
     * @Template()
     */
    public function adminAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $projets = $user->getProjets();
        $etapes = $this->getDoctrine()
        ->getRepository('PhilaeBundle:Etape')
        ->findByUser($user->getId());

        return $this->render('PhilaeBundle:Default:admin.html.twig', array('projets' => $projets, 'etapes' => $etapes ));
    }


    /**
     * @Route("/admin/ajoutEtape/{idProjet}/")
     * @Template()
     */
    public function ajoutEtapeAction($idProjet)

    {

        $user = $this->getUser();

        $projet = $this->getDoctrine()
            ->getRepository('PhilaeBundle:Projet')->find($idProjet);

        $projets = $user->getProjets();

        $ajoutApprouve = false;

        for ($i = 0; $i <= count($projets); $i++) {
            if (isset($projets[$i])) {
                if ($projets[$i]->getId() == $projet->getId()) {
                    $ajoutApprouve = true;
                }
            }
        }
        if ($ajoutApprouve) {
                    $user = $this->getUser();

                    // On crée un objet Article
                    $article = new Etape();
                    $article->setUser($user);
                    $article->setProjet($projet);
                    $article->setIsValide(Etape::ATTENTE_VALIDATION);
                    // J'ai raccourci cette partie, car c'est plus rapide à écrire !
                    $form = $this->createFormBuilder($article)

                        ->add('titre', 'text')
                        ->add('contenu', 'textarea', array(
                            'attr' => array(
                                'class' => 'tinymce',
                                'data-theme' => 'bbcode' // Skip it if you want to use default theme
                            )
                        ))
                        ->add('date', 'date')
                        ->add('file')
                        ->add('avancement', 'integer')
                        ->add('save', 'submit')
                        ->getForm();

                    // On récupère la requête
                    $request = $this->get('request');

                    // On vérifie qu'elle est de type POST
                    if ($request->getMethod() == 'POST') {
                        // On fait le lien Requête <-> Formulaire
                        // À partir de maintenant, la variable $article contient les valeurs entrées dans le formulaire par le visiteur
                        $form->bind($request);

                        // On vérifie que les valeurs entrées sont correctes
                        // (Nous verrons la validation des objets en détail dans le prochain chapitre)
                        if ($form->isValid()) {
                            // On l'enregistre notre objet $article dans la base de données
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($article);
                            $em->flush();

                            // On redirige vers la page de visualisation de l'article nouvellement créé
                            return $this->redirect($this->generateUrl('cnes_philae_default_admin'));
                        }
                    }

                    // À ce stade :
                    // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
                    // - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau


                    // On passe la méthode createView() du formulaire à la vue afin qu'elle puisse afficher le formulaire toute seule
                    return $this->render('PhilaeBundle:Default:ajoutEtape.html.twig', array(
                        'form' => $form->createView()));


        } else {
            throw $this->createNotFoundException('Vous n\'avez pas le droit d\'accédez à cet page');
        }
    }


    /**
     * @Route("/admin/modifEtape/{id}/")
     * @Template()
     */
    public function modifierAction($id)
    {
        $user = $this->getUser();

        $projets = $user->getProjets();

        //Récupère l'idProjet selon l'étape en cours
        $projetEtape = $this->getDoctrine()
            ->getRepository('PhilaeBundle:Etape')
            ->find($id)->getProjet();

        $modificationApprouve = false;

        for ($i = 0; $i <= count($projets); $i++) {
            if (isset($projets[$i])) {
                if ($projets[$i]->getId() == $projetEtape->getId()) {
                    $modificationApprouve = true;
                }
            }
        }

        if ($modificationApprouve) {
            // On récupère l'EntityManager
            $em = $this->getDoctrine()->getManager();

            // On récupère l'entité correspondant à l'id $id
            $article = $em->getRepository('PhilaeBundle:Etape')
                ->find($id);

            // Si l'article n'existe pas, on affiche une erreur 404
            if ($article == null) {
                throw $this->createNotFoundException('Article[id=' . $id . '] inexistant');
            }
            $article->setIsValide(Etape::ATTENTE_VALIDATION);
            // J'ai raccourci cette partie, car c'est plus rapide à écrire !
            $form = $this->createFormBuilder($article)

                ->add('titre', 'text')
                ->add('contenu', 'textarea', array(
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'bbcode' // Skip it if you want to use default theme
                )
            ))
                ->add('date', 'date')
                ->add('file')
                ->add('avancement', 'integer')
                ->add('save', 'submit')

                ->getForm();

            // On récupère la requête
            $request = $this->get('request');

            // On vérifie qu'elle est de type POST
            if ($request->getMethod() == 'POST') {
                // On fait le lien Requête <-> Formulaire
                // À partir de maintenant, la variable $article contient les valeurs entrées dans le formulaire par le visiteur
                $form->bind($request);

                // On vérifie que les valeurs entrées sont correctes
                // (Nous verrons la validation des objets en détail dans le prochain chapitre)
                if ($form->isValid()) {
                    // On l'enregistre notre objet $article dans la base de données
                    $em = $this->getDoctrine()->getManager();

                    $em->persist($article);
                    $em->flush();


                  // On redirige vers la page de visualisation de l'article nouvellement créé

                    return $this->redirect($this->generateUrl('cnes_philae_default_admin'));
                }
            }

            return $this->render('PhilaeBundle:Default:ajoutEtape.html.twig', array(
                'form' => $form->createView()));

        } else {
            throw $this->createNotFoundException('Vous n\'avez pas le droit d\'accédez à cet page');
        }
    }







/**
 * @Route("/admin/delete/etape/{id}/")
 * @Template()
 */
public
function deleteEtapeAction($id)
{

    $user = $this->getUser();


    $projets = $user->getProjets();

    //Récupère l'idProjet selon l'étape en cours
    $projetEtape = $this->getDoctrine()
        ->getRepository('PhilaeBundle:Etape')
        ->find($id)->getProjet();

    $deleteApprouve = false;

    for ($i = 0; $i <= count($projets); $i++) {
        if (isset($projets[$i])) {
            if ($projets[$i]->getId() == $projetEtape->getId()) {
                $deleteApprouve = true;
            }
        }
    }

    if ($deleteApprouve) {
            $em = $this->getDoctrine()->getManager();
            $etape = $em->getRepository('PhilaeBundle:Etape')->find($id);
            $etape->setIsValide(Etape::SUPPRIMER);
            $em->flush();


        return $this->redirect($this->generateUrl('cnes_philae_default_admin'));

    } else {
        throw $this->createNotFoundException('Vous n\'avez pas le droit d\'accédez à cet page');
    }
}


    /**
     * @Route("/gestion/")
     * @Template()
     */
    public function gestionAction()
    {
        $user = $this->getUser();
        $projets = $user->getProjets();
        $etapes = $this->getDoctrine()
            ->getRepository('PhilaeBundle:Etape')
            ->findByUser($user->getId());

        return $this->render('PhilaeBundle:Default:gestion.html.twig', array('projets' => $projets, 'lesEtapes' => $etapes ));
    }

    /**
     * @Route("/gestion/publierEtape/{id}")
     * @Template()
     */
    public function publierGestionAction($id)
    {

        $user = $this->getUser();

        $projets = $user->getProjets();

        //Récupère l'idProjet selon l'étape en cours
        $projetEtape = $this->getDoctrine()
            ->getRepository('PhilaeBundle:Etape')
            ->find($id)->getProjet();


        $publierApprouve = false;

        for ($i = 0; $i <= count($projets); $i++) {
            if (isset($projets[$i])) {
                if ($projets[$i]->getId() == $projetEtape->getId()) {
                    $publierApprouve = true;
                }
            }
        }

        if ($publierApprouve) {
        $em= $this->getDoctrine()->getManager();
        $etape=$em->getRepository('PhilaeBundle:Etape')->find($id);

        $etape->setIsValide(Etape::VALIDE);

        $em->flush();

       // if ($this->get('security.context')->isGranted('ROLE_GESTIONNAIRE'))

            return $this->redirect($this->generateUrl('cnes_philae_default_gestion'));
        } else {
            throw $this->createNotFoundException('Vous n\'avez pas le droit d\'accédez à cet page');
        }
    }


    /**
     * @Route("/gestion/modifEtape/{id}/")
     * @Template()
     */
    public function modifierGestionAction($id)
    {

        $user = $this->getUser();

        $projets = $user->getProjets();

        //Récupère l'idProjet selon l'étape en cours
        $projetEtape = $this->getDoctrine()
            ->getRepository('PhilaeBundle:Etape')
            ->find($id)->getProjet();

        $modificationApprouve = false;

        for ($i = 0; $i <= count($projets); $i++) {
            if (isset($projets[$i])) {
                if ($projets[$i]->getId() == $projetEtape->getId()) {
                    $modificationApprouve = true;
                }
            }
        }

        if ($modificationApprouve) {
            // On récupère l'EntityManager
            $em = $this->getDoctrine()->getManager();

            // On récupère l'entité correspondant à l'id $id
            $article = $em->getRepository('PhilaeBundle:Etape')
                ->find($id);

            // Si l'article n'existe pas, on affiche une erreur 404
            if ($article == null) {
                throw $this->createNotFoundException('Article[id=' . $id . '] inexistant');
            }

            // J'ai raccourci cette partie, car c'est plus rapide à écrire !
            $form = $this->createFormBuilder($article)

                ->add('titre', 'text')
                ->add('contenu', 'textarea', array(
                    'attr' => array(
                        'class' => 'tinymce',
                        'data-theme' => 'bbcode' // Skip it if you want to use default theme
                    )
                ))
                ->add('date', 'date')
                ->add('file')
                ->add('avancement', 'integer')
                ->add('save', 'submit')
                ->getForm();

            // On récupère la requête
            $request = $this->get('request');

            // On vérifie qu'elle est de type POST
            if ($request->getMethod() == 'POST') {
                // On fait le lien Requête <-> Formulaire
                // À partir de maintenant, la variable $article contient les valeurs entrées dans le formulaire par le visiteur
                $form->bind($request);

                // On vérifie que les valeurs entrées sont correctes
                // (Nous verrons la validation des objets en détail dans le prochain chapitre)
                if ($form->isValid()) {
                    // On l'enregistre notre objet $article dans la base de données
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($article);
                    $em->flush();

                    // On redirige vers la page de visualisation de l'article nouvellement créé
                    return $this->redirect($this->generateUrl('cnes_philae_default_gestion'));
                }
            }

            return $this->render('PhilaeBundle:Default:ajoutEtape.html.twig', array(
                'form' => $form->createView()));

        } else {
            throw $this->createNotFoundException('Vous n\'avez pas le droit d\'accédez à cet page');
        }
    }

    /**
     * @Route("/gestion/delete/etape/{id}/")
     * @Template()
     */
    public
    function deleteGestionAction($id)
    {

        $user = $this->getUser();

        $projets = $user->getProjets();

        //Récupère l'idProjet selon l'étape en cours
        $projetEtape = $this->getDoctrine()
            ->getRepository('PhilaeBundle:Etape')
            ->find($id)->getProjet();

        $deleteApprouve = false;

        for ($i = 0; $i <= count($projets); $i++) {
            if (isset($projets[$i])) {
                if ($projets[$i]->getId() == $projetEtape->getId()) {
                    $deleteApprouve = true;
                }
            }
        }

        if ($deleteApprouve) {
            $em = $this->getDoctrine()->getManager();
            $etape = $em->getRepository('PhilaeBundle:Etape')->find($id);
            $etape->setIsValide(Etape::SUPPRIMER);
            $em->flush();


            return $this->redirect($this->generateUrl('cnes_philae_default_gestion'));

        } else {
            throw $this->createNotFoundException('Vous n\'avez pas le droit d\'accédez à cet page');
        }
    }


    /**
     * @Route("/utilisateurs/")
     * @Template()
     */
    public
    function AfficherUserAction()
    {
        $userManager=$this->get('fos_user.user_manager');
        $users=$userManager->findUsers();

        return $this->render('PhilaeBundle:Default:users.html.twig', array('users' =>$users));

    }




}



