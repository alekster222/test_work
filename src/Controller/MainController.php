<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use App\Entity\Goods;

class MainController extends Controller
{
    /**
     * @Route("/", name="main")
     * @var Request $request
     * @return string
     */
    public function index(Request $request) {
		$client = $this->get('eight_points_guzzle.client.parser');
		$repo = $this->getDoctrine()->getRepository(Goods::class);

		$crawler = $repo->parsePages($client, '/mobilnye-telefony/?iPageNo=2');

		$counter = 1;
		$nextPage = $crawler->filter('.b-pagination-list .p-next a.p-inside')->count();

		while ($nextPage) {
			$nextUrl = parse_url($crawler->filter('.b-pagination-list .p-next a.p-inside')->attr('href'));

			$crawler = $repo->parsePages($client, $nextUrl['path'] . '?' . $nextUrl['query']);

			$nextPage = $crawler->filter('.b-pagination-list .p-next a.p-inside')->count();

			$counter++;
			if ($counter >3 ) {
				break;
			}
		}

		return $this->render('base.html.twig', array(

        ));
    }
}
