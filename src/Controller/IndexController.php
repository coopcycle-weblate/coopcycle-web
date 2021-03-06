<?php

namespace AppBundle\Controller;

use AppBundle\Annotation\HideSoftDeleted;
use AppBundle\Controller\Utils\UserTrait;
use AppBundle\Entity\LocalBusiness;
use AppBundle\Entity\LocalBusinessRepository;
use AppBundle\Enum\FoodEstablishment;
use AppBundle\Enum\Store;
use MyCLabs\Enum\Enum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class IndexController extends AbstractController
{
    use UserTrait;

    const MAX_RESULTS = 3;
    const EXPIRES_AFTER = 300;

    private function getItems(LocalBusinessRepository $repository, string $type, CacheInterface $cache, string $cacheKey)
    {
        $typeRepository = $repository->withContext($type);

        $itemsIds = $cache->get($cacheKey, function (ItemInterface $item) use ($typeRepository) {

            $item->expiresAfter(self::EXPIRES_AFTER);

            $items = $typeRepository->findAllSorted();

            return array_map(fn(LocalBusiness $lb) => $lb->getId(), $items);
        });

        foreach (array_slice($itemsIds, 0, self::MAX_RESULTS) as $id) {
            if (null === $typeRepository->find($id)) {
                $cache->delete($cacheKey);

                return $this->getItems($repository, $type, $cache, $cacheKey);
            }
        }

        $count = count($itemsIds);
        $items = array_map(
            fn(int $id): LocalBusiness => $typeRepository->find($id),
            array_slice($itemsIds, 0, self::MAX_RESULTS)
        );

        return [ $items, $count ];
    }

    /**
     * @HideSoftDeleted
     */
    public function indexAction(LocalBusinessRepository $repository, CacheInterface $appCache)
    {
        $user = $this->getUser();

        if ($user && ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_RESTAURANT'))) {
            $cacheKeySuffix = $user->getUsername();
        } else {
            $cacheKeySuffix = 'anonymous';
        }

        [ $restaurants, $restaurantsCount ] =
            $this->getItems($repository, FoodEstablishment::class, $appCache, sprintf('homepage.restaurants.%s', $cacheKeySuffix));
        [ $stores, $storesCount ] =
            $this->getItems($repository, Store::class, $appCache, sprintf('homepage.stores.%s', $cacheKeySuffix));

        return $this->render('index/index.html.twig', array(
            'restaurants' => $restaurants,
            'stores' => $stores,
            'show_more_restaurants' => $restaurantsCount > self::MAX_RESULTS,
            'show_more_stores' => $storesCount > self::MAX_RESULTS,
            'max_results' => self::MAX_RESULTS,
            'addresses_normalized' => $this->getUserAddresses(),
        ));
    }
}
