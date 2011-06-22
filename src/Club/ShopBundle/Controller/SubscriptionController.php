<?php

namespace Club\ShopBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SubscriptionController extends Controller
{
  /**
   * @Route("/shop/subscription", name="shop_subscription")
   * @Template()
   */
  public function indexAction()
  {
    $user = $this->get('security.context')->getToken()->getUser();

    return array(
      'user' => $user
    );
  }

  /**
   * @Route("/shop/subscription/pause/{id}", name="shop_subscription_pause")
   * @Template()
   */
  public function pauseAction($id)
  {
    $em = $this->getDoctrine()->getEntityManager();
    $subscription = $em->find('ClubShopBundle:Subscription',$id);

    $this->get('subscription')->pauseSubscription($subscription);

    return $this->redirect($this->generateUrl('shop_subscription'));
  }

  /**
   * @Route("/shop/subscription/resume/{id}", name="shop_subscription_resume")
   * @Template()
   */
  public function resumeAction($id)
  {
    $em = $this->getDoctrine()->getEntityManager();
    $subscription = $em->find('ClubShopBundle:Subscription',$id);

    $this->get('subscription')->resumeSubscription($subscription);

    return $this->redirect($this->generateUrl('shop_subscription'));
  }
}
