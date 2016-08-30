<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\View\TwitterBootstrap3View;

use AppBundle\Entity\Expense;
use AppBundle\Form\ExpenseType;

use AppBundle\Form\ExpenseFilterType;

/**
 * Expense controller.
 *
 * @Route("/admin/expenses")
 */
class ExpenseController extends Controller
{
    /**
     * Lists all Expense entities.
     *
     * @Route("/", name="expense")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->getRepository('AppBundle:Expense')->createQueryBuilder('e');
        list($filterForm, $queryBuilder) = $this->filter($queryBuilder, $request);

        list($expenses, $pagerHtml) = $this->paginator($queryBuilder, $request);
        
        return $this->render('@App/Expense/index.html.twig', array(
            'expenses' => $expenses,
            'pagerHtml' => $pagerHtml,
            'filterForm' => $filterForm->createView(),

        ));
    }

    
    /**
    * Create filter form and process filter request.
    *
    */
    protected function filter($queryBuilder, $request)
    {
        $session = $request->getSession();
        $filterForm = $this->createForm('AppBundle\Form\ExpenseFilterType');

        // Reset filter
        if ($request->get('filter_action') == 'reset') {
            $session->remove('ExpenseControllerFilter');
        }

        // Filter action
        if ($request->get('filter_action') == 'filter') {
            // Bind values from the request
            $filterForm->submit($request->query->get($filterForm->getName()));

            if ($filterForm->isValid()) {
                // Build the query from the given form object
                $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($filterForm, $queryBuilder);
                // Save filter to session
                $filterData = $filterForm->getData();
                $session->set('ExpenseControllerFilter', $filterData);
            }
        } else {
            // Get filter from session
            if ($session->has('ExpenseControllerFilter')) {
                $filterData = $session->get('ExpenseControllerFilter');
                $filterForm = $this->createForm('AppBundle\Form\ExpenseFilterType', $filterData);
                $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($filterForm, $queryBuilder);
            }
        }

        return array($filterForm, $queryBuilder);
    }

    /**
    * Get results from paginator and get paginator view.
    *
    */
    protected function paginator($queryBuilder, $request)
    {
        // Paginator
        $adapter = new DoctrineORMAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $currentPage = $request->get('page', 1);
        $pagerfanta->setCurrentPage($currentPage);
        $entities = $pagerfanta->getCurrentPageResults();

        // Paginator - route generator
        $me = $this;
        $routeGenerator = function($page) use ($me)
        {
            return $me->generateUrl('expense', array('page' => $page));
        };

        // Paginator - view
        $view = new TwitterBootstrap3View();
        $pagerHtml = $view->render($pagerfanta, $routeGenerator, array(
            'proximity' => 3,
            'prev_message' => 'previous',
            'next_message' => 'next',
        ));

        return array($entities, $pagerHtml);
    }
    
    

    /**
     * Displays a form to create a new Expense entity.
     *
     * @Route("/new", name="expense_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
    
        $expense = new Expense();
        $form   = $this->createForm('AppBundle\Form\ExpenseType', $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($expense);
            $em->flush();

            return $this->redirectToRoute('expense_show', array('id' => $expense->getId()));
        }
        return $this->render('@App/Expense/new.html.twig', array(
            'expense' => $expense,
            'form'   => $form->createView(),
        ));
    }
    
    

    
    /**
     * Finds and displays a Expense entity.
     *
     * @Route("/{id}", name="expense_show")
     * @Method("GET")
     */
    public function showAction(Expense $expense)
    {
        $deleteForm = $this->createDeleteForm($expense);
        return $this->render('@App/Expense/show.html.twig', array(
            'expense' => $expense,
            'delete_form' => $deleteForm->createView(),
        ));
    }
    
    

    /**
     * Displays a form to edit an existing Expense entity.
     *
     * @Route("/{id}/edit", name="expense_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Expense $expense)
    {
        $deleteForm = $this->createDeleteForm($expense);
        $editForm = $this->createForm('AppBundle\Form\ExpenseType', $expense);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($expense);
            $em->flush();
            
            $this->get('session')->getFlashBag()->add('success', 'Edited Successfully!');
            return $this->redirectToRoute('expense_edit', array('id' => $expense->getId()));
        }
        return $this->render('@App/Expense/edit.html.twig', array(
            'expense' => $expense,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    
    

    /**
     * Deletes a Expense entity.
     *
     * @Route("/{id}", name="expense_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Expense $expense)
    {
    
        $form = $this->createDeleteForm($expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($expense);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'flash.delete.success');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'flash.delete.error');
        }
        
        return $this->redirectToRoute('expense');
    }
    
    /**
     * Creates a form to delete a Expense entity.
     *
     * @param Expense $expense The Expense entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Expense $expense)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('expense_delete', array('id' => $expense->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
    
    /**
     * Delete Expense by id
     *
     * @param mixed $id The entity id
     * @Route("/delete/{id}", name="expense_by_id_delete")
     * @Method("GET")
     */
    public function deleteById($id){

        $em = $this->getDoctrine()->getManager();
        $expense = $em->getRepository('AppBundle:Expense')->find($id);
        
        if (!$expense) {
            throw $this->createNotFoundException('Unable to find Expense entity.');
        }
        
        try {
            $em->remove($expense);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'flash.delete.success');
        } catch (Exception $ex) {
            $this->get('session')->getFlashBag()->add('error', 'flash.delete.error');
        }

        return $this->redirect($this->generateUrl('expense'));

    }
    
    
    
    /**
    * Bulk Action
    * @Route("/bulk-action/", name="expense_bulk_action")
    * @Method("POST")
    */
    public function bulkAction(Request $request)
    {
        $ids = $request->get("ids", array());
        $action = $request->get("bulk_action", "delete");

        if ($action == "delete") {
            try {
                $em = $this->getDoctrine()->getManager();
                $repository = $em->getRepository('AppBundle:Expense');

                foreach ($ids as $id) {
                    $expense = $repository->find($id);
                    $em->remove($expense);
                    $em->flush();
                }

                $this->get('session')->getFlashBag()->add('success', 'expenses was deleted successfully!');

            } catch (Exception $ex) {
                $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the expenses ');
            }
        }

        return $this->redirect($this->generateUrl('expense'));
    }
    
    
}
