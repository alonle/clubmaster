<?php

namespace Club\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * User
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class User extends EntityRepository
{
  public function findAllActive()
  {
    $dql =
      "SELECT u,s ".
      "FROM Club\UserBundle\Entity\User u ".
      "JOIN u.subscriptions s ".
      "WHERE s.start_date <= :start AND s.expire_date <= :expire";

    $query = $this->_em->createQuery($dql);
    $query->setParameters(array(
      'start' => date('Y-m-d'),
      'expire' => date('Y-m-d')
    ));

    return $query->getResult();
  }

  public function findNextMemberNumber()
  {
    $dql = "SELECT u FROM Club\UserBundle\Entity\User u ORDER BY u.member_number DESC";

    $query = $this->_em->createQuery($dql);
    $query->setMaxResults(1);

    $r = $query->getResult();

    if (!count($r)) return 1;
    return $r[0]->getMemberNumber()+1;
  }

  public function getUsers($filter)
  {
    $dql = "SELECT u FROM Club\UserBundle\Entity\User u LEFT JOIN u.profile p WHERE (CONCAT(p.first_name,p.last_name) LIKE ?1 OR u.member_number = ?2)";

    $users = $this->_em->createQuery($dql)
      ->setParameter(1,'%'.preg_replace("/\s/","",$filter->getMemberNumber().'%'))
      ->setParameter(2,$filter->getMemberNumber())
      ->getResult();

    return $users;
  }

  public function getUsersListWithPagination($filter, $order_by = array(), $offset = 0, $limit = 0) {
    //Create query builder for languages table
    $qb = $this->getQuery($filter);

    //Show all if offset and limit not set, also show all when limit is 0
    if ((isset($offset)) && (isset($limit))) {
      if ($limit > 0) {
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
      }
      //else we want to display all items on one page
    }
    //Adding defined sorting parameters from variable into query
    foreach ($order_by as $key => $value) {
      $qb->add('orderBy', 'u.' . $key . ' ' . $value);
    }
    //Get our query
    $q = $qb->getQuery();
    //Return result
    return $q->getResult();
  }

  public function getUsersCount($filter) {
    $qb = $this->getQuery($filter);

    $qb->select($qb->expr()->count('u'));
    $q = $qb->getQuery();
    return $q->getSingleScalarResult();
  }

  protected function getQuery($filter)
  {
    $qb = $this->createQueryBuilder('u');

    if ($filter->getMemberNumber()) {
      $qb->andWhere('u.member_number = ?1')->setParameter(1,$filter->getMemberNumber());
    }

    return $qb;
  }
}
