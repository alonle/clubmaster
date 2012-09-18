<?php

namespace Club\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/{_locale}/members")
 */
class MemberController extends Controller
{
  /**
   * @Template()
   * @Route("/search")
   */
  public function searchAction()
  {
    $em = $this->getDoctrine()->getEntityManager();
    $form = $this->createForm(new \Club\UserBundle\Form\UserAjax());

    if ($this->getRequest()->getMethod() == 'POST') {
      $form->bind($this->getRequest());
      if ($form->isValid()) {
          $user = $form->get('user')->getData();

          return $this->redirect($this->generateUrl('club_user_member_show', array('id' => $user->getId())));
      } else {
          $errors = $form->get('user')->getErrors();

          foreach ($errors as $error) {
              $this->get('session')->setFlash('error', $error->getMessage());
          }
      }
    }

    return $this->redirect($this->generateUrl('club_user_member_index'));
  }

  /**
   * @Template()
   * @Route("/{id}")
   */
  public function showAction(\Club\UserBundle\Entity\User $user)
  {
    $event = new \Club\UserBundle\Event\FilterOutputEvent();
    $event->setUser($user);
    $this->get('event_dispatcher')->dispatch(\Club\UserBundle\Event\Events::onMemberView, $event);

    return array(
      'user' => $user,
      'output' => $event->getOutput()
    );
  }

  /**
   * @Template()
   * @Route("", defaults={"page" = 1 })
   * @Route("/page/{page}", name="club_user_members_page")
   */
  public function indexAction($page)
  {
      $results = 50;

      $em = $this->getDoctrine()->getEntityManager();
      $form = $this->createForm(new \Club\UserBundle\Form\UserAjax());

      $paginator = $em->getRepository('ClubUserBundle:User')->getPaginator($results, $page);

      $nav = $this->get('club_paginator.paginator')
          ->init($results, count($paginator), $page, 'club_user_members_page');

      return array(
          'form' => $form->createView(),
          'paginator' => $paginator,
          'nav' => $nav
      );
  }
}
