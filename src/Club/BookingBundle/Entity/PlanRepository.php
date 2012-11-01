<?php

namespace Club\BookingBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * PlanRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PlanRepository extends EntityRepository
{
    public function getAllBetween(\DateTime $start, \DateTime $end, \Club\UserBundle\Entity\Location $location=null, \Club\BookingBundle\Entity\Field $field=null)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('p')
            ->from('ClubBookingBundle:Plan', 'p')
            ->leftJoin('p.fields', 'f')
            ->where('p.day LIKE :day')
            ->andWhere('p.period_start <= :period_start AND p.period_end >= :period_end')
            ->andWhere('(p.first_time <= :start and p.end_time >= :end) OR (p.first_time <= :start and p.end_time <= :end and p.end_time >= :start) OR (p.first_time >= :start and p.end_time >= :end and p.first_time < :end) OR (p.end_time >= :start and p.end_time <= :end and p.end_time >= :start)')
            ->setParameter('day', '%'.$start->format('N').'%')
            ->setParameter('period_start', $start)
            ->setParameter('period_end', $end)
            ->setParameter('start', $start->format('H:i:s'))
            ->setParameter('end', $end->format('H:i:s'));

        if ($location) {
            $qb
                ->andWhere('f.location = :location')
                ->setParameter('location', $location->getId());
        }

        if ($field) {
            $qb
                ->andWhere('f.id = :field')
                ->setParameter('field', $field->getId());
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function getICSByField(\Club\BookingBundle\Entity\Field $field)
    {
        $plans = $this->createQueryBuilder('p')
            ->join('p.fields', 'f')
            ->join('p.plan_repeats', 'pr')
            ->where('f.id = :field')
            ->andWhere('((pr.ends_type != :type) OR (pr.ends_type = :type AND pr.ends_on > :date))')
            ->setParameter('field', $field->getId())
            ->setParameter('date', new \DateTime())
            ->setParameter('type', 'on')
            ->getQuery()
            ->getResult();

        return $this->getIcsFromPlans($plans);
    }

    public function getIcsFromPlans($plans)
    {
        $ics = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0

EOF;

        foreach ($plans as $plan) {
            if ($plan->getRepeating()) {
                foreach ($plan->getPlanRepeats() as $repeat) {
                    $ics .= $this->addFreq($repeat);
                }

            } else {
                $ics .= $this->addEvent($plan);
            }
        }

        $ics .= <<<EOF
END:VCALENDAR
EOF;

        return $ics;
    }

    public function getICSByLocation(\Club\UserBundle\Entity\Location $location)
    {
        $plans = $this->createQueryBuilder('p')
            ->join('p.fields', 'f')
            ->join('p.plan_repeats', 'pr')
            ->where('f.location = :location')
            ->setParameter('location', $location->getId())
            ->getQuery()
            ->getResult();

        return $this->getIcsFromPlans($plans);
    }

    public function getBetweenByField(\Club\BookingBundle\Entity\Field $field, \DateTime $start, \DateTime $end)
    {
        $ics = $this->getICSByField($field);

        return $this->getPlansFromIcs($ics, $start, $end);
    }

    public function getPlansFromIcs($ics, \DateTime $start, \DateTime $end)
    {
        $calendar = \Sabre\VObject\Reader::read($ics);
        $calendar->expand($start, $end);

        $plans = array();
        if (count($calendar->VEVENT)) {
            foreach ($calendar->VEVENT as $event) {
                preg_match("/^(\d+)_/", $event->UID, $o);
                $plan_id = $o[1];
                $plan = clone $this->_em->find('ClubBookingBundle:Plan', $plan_id);
                $plan->setStart($event->DTSTART->getDateTime());
                $plan->setEnd($event->DTEND->getDateTime());

                $plans[] = $plan;
            }
        }

        return $plans;
    }

    public function getBetweenByLocation(\Club\UserBundle\Entity\Location $location, \DateTime $start, \DateTime $end)
    {
        $ics = $this->getICSByLocation($location);

        return $this->getPlansFromIcs($ics, $start, $end);
    }

    private function addFreq(\Club\BookingBundle\Entity\PlanRepeat $repeat)
    {
        $plan = $repeat->getPlan();

        $exception = '';
        foreach ($plan->getPlanExceptions() as $e) {
            $exception .= 'EXDATE:'.$e->getExcludeDate()->format('Ymd\THis').PHP_EOL;
        }

        $ics = <<<EOF
BEGIN:VEVENT
UID:{$repeat->getIcsUid()}
DTSTAMP:{$plan->getCreatedAt()->format('Ymd\THis')}
DTSTART:{$plan->getStart()->format('Ymd\THis')}
DTEND:{$plan->getEnd()->format('Ymd\THis')}
SUMMARY:{$plan->getName()}
RRULE:{$repeat->getIcsFreq()}
{$exception}
END:VEVENT

EOF;

        return $ics;
    }

    private function addEvent(\Club\BookingBundle\Entity\Plan $plan)
    {
        $ics = <<<EOF
BEGIN:VEVENT
UID:{$plan->getIcsUid()}
DTSTAMP:{$plan->getCreatedAt()->format('Ymd\THis')}
DTSTART:{$plan->getStart()->format('Ymd\THis')}
DTEND:{$plan->getEnd()->format('Ymd\THis')}
SUMMARY:{$plan->getName()}
END:VEVENT

EOF;

        return $ics;
    }
}
