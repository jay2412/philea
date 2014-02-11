<?php

namespace Cnes\PhilaeBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EtapeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EtapeRepository extends EntityRepository
{
    public function getEtapes($id)
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT e, MAX(e.avancement), p FROM PhilaeBundle:Etape e JOIN e.projet p LEFT JOIN p.domaine d WHERE d.id = :id GROUP BY p.id')
            ->setParameter('id', $id);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
