<?php

namespace App\Repository;

use App\Entity\Goods;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DomCrawler\Crawler;
use App\Entity\Comments;

/**
 * @method Goods|null find($id, $lockMode = null, $lockVersion = null)
 * @method Goods|null findOneBy(array $criteria, array $orderBy = null)
 * @method Goods[]    findAll()
 * @method Goods[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GoodsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Goods::class);
    }

	public function parsePages($client, $url) {

		$categoryHtml = $client->request('GET', $url)->getBody()->getContents();
		$crawlerCategory = new Crawler($categoryHtml);

		$goodsInfo = $crawlerCategory
			->filter('.cItems_col')
			->each(function (Crawler $node, $i) {

				$item['title'] = $node->filter('.cItems_title')->text();
				$item['link'] = $node->filter('a.full-link')->attr('data-compare_link');
				$item['img'] = $node->filter('.media img')->attr('src');

				if ($node->filter('meta[itemprop="lowPrice"]')->count() > 0) {
					$item['min_cost'] = $node->filter('meta[itemprop="lowPrice"]')->attr('content');
				} else {
					$item['min_cost'] = $node->filter('meta[itemprop="price"]')->attr('content');
				}

				return $item;
			});

		foreach($goodsInfo as $goods) {
			$pageHtml = $client->request('GET', '/'.$goods['link'])->getBody()->getContents();
			$crawlerPage = new Crawler($pageHtml);

			$shops = $crawlerPage->filter('table.item_table tr')->count();
			$comments = (int) $crawlerPage
						->filter('.item_tabs ul li')
						->eq(3)
						->filter('span.num')
						->text();

			$brand = $crawlerPage
						->filter('ul.brc.brc_blue>li>a')											
						->eq(2)
						->filter('meta[itemprop="name"]')
						->attr('content');		

			$goodsObj = new Goods();
			$goodsObj->setTitle($goods['title']);
			$goodsObj->setImage($goods['img']);
			$goodsObj->setMinCost($goods['min_cost']);
			$goodsObj->setBrand($brand);
			$goodsObj->setShops($shops);
			$goodsObj->setComments($comments);

			$this->_em->persist($goodsObj);

			if ($comments > 0) {
				$commentHtml = $client->request('GET', '/'.$goods['link'].'?comments')->getBody()->getContents();
				$crawlerComments = new Crawler($commentHtml);

				$commentBlocks = $crawlerComments
									->filter('#comments .feedback-i')
									->each(function (Crawler $node, $i) {
										return $node->html();
									});

				foreach($commentBlocks as $commentBlock) {
					$comment = new Comments();
					$comment->setGoods($goodsObj);
					$comment->setText($commentBlock);

					$this->_em->persist($comment);
				}
			}

			$this->_em->flush();
		}

		return $crawlerCategory;
	}

//    /**
//     * @return Goods[] Returns an array of Goods objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Goods
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
