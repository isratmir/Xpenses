<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Expense;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/worker")
 */
class FrontendController extends Controller
{
    /**
     * @Route("/", name="worker_index")
     */
    public function indexAction()
    {
	    $user = $this->getUser();
	    $expenses = $user->getExpenses();
	    $expensesArray = array();
	    foreach ( $expenses as $expense ) {
		    $expensesArray[] = array(
			      'id' => $expense->getId(),
				    'title' => $expense->getTitle(),
				    'price' => $expense->getPrice(),
				    'created_at' => $expense->getCreatedAt()
		    );
	    }

	    return $this->render('AppBundle:Frontend:index.html.twig', array(
				'expenses' => json_encode($expensesArray)
      ));
    }

		/**
	  * @Route("/statistic", name="worker_statistic")
	  */
		public function statisticAction()
		{
			return $this->render("AppBundle:Frontend:statistic.html.twig", [

			]);
		}

    /**
     * @Route("/ajax/add", name="add_expense")
     */
    public function addAjaxExpenseAction(Request $request)
    {
	    $item = $request->get('item');

	    $user = $this->getUser();

	    $expense = new Expense();
	    $expense->setTitle($item[0]['title']);
	    $expense->setPrice($item[0]['price']);
	    $expense->setUser($user);

	    $em = $this->getDoctrine()->getManager();
	    $em->persist($expense);
	    $em->flush();

	    return new JsonResponse(['id' => $expense->getId()]);
    }

    /**
     * @Route("/ajax/remove", name="remove_expense")
     */
    public function removeAjaxExpenseAction(Request $request)
    {
	    $expense = $this->getDoctrine()->getRepository('AppBundle:Expense')
			    ->find($request->get('id'));

	    $em = $this->getDoctrine()->getManager();
	    $em->remove($expense);
	    $em->flush();

	    return new Response('ok');
    }

		/**
	  * @return array
		*
		* @Route("/ajax/get", name="get_expenses")
	  */
		public function getAjaxExpensesAction()
		{
			$user = $this->getUser();
			$expenses = $user->getExpenses();
			$expensesArray = array();
			foreach ( $expenses as $expense ) {

				$expensesArray[] = array(
						'id' => $expense->getId(),
						'title' => $expense->getTitle(),
						'price' => $expense->getPrice(),
						'created_at' => $expense->getCreatedAt()
				);
			}

			return new JsonResponse($expensesArray);
		}
}
