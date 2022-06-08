<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    public function paginateItems(QueryBuilder $qb, Request $request): iterable
    {
        $currentPage = $request->query->get('page', 1);
        $maxItemsPerPage = $this->parameterBag->get('pagination.items_per_page');

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxItemsPerPage);
        $pagerfanta->setCurrentPage($currentPage);

        return $pagerfanta;
    }
}
