<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class FeedsReaderController extends Controller
{
    const POSTS_PER_PAGE = 3;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * FeedsReaderController constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/{page}", name="feed_list", defaults={"page" = 1})
     */
    public function listAction(Request $request)
    {
        $page = $request->get('page');

        if (empty($page)) {
            $page = 1;
        }

        $offset = self::POSTS_PER_PAGE*($page - 1);

        /** @var Query $query */
        $query = $this->em->getRepository('AppBundle:Post')->createQueryBuilder('post');
        $query->setFirstResult($offset)
            ->leftJoin(
                'AppBundle\Entity\Category',
                'c',
                Join::WITH,
                'post.category = c.id'
            )
            ->orderBy('post.id', 'DESC')
            ->setMaxResults(self::POSTS_PER_PAGE);

        if (!empty($request->get('category'))) {
            $query->where('c = :category')
                ->setParameter('category', $request->get('category'));
        }

        $posts = new Paginator($query);
        $count = count($posts);
        $pages = ceil($count / self::POSTS_PER_PAGE);
        $categories = $this->em->getRepository("AppBundle:Category")->findAll();

        return $this->render(
            'feedsreader/index.html.twig',
            ["posts" => $posts, 'categories' => $categories, 'pages' => $pages]
        );
    }

    /**
     * @Route("/create", name="feed_create")
     */
    public function createAction(Request $request)
    {
        $post = new Post;

        $form = $this->createFormBuilder($post)
            ->add('name', TextType::class, ['attr' => ['form-control']])
            ->add('description', TextareaType::class, ['attr' => ['form-control']])
            ->add('category', EntityType::class, [
                'class' => 'AppBundle:Category',
                'choice_label' => 'name',
            ])
            ->add('save', SubmitType::class, ['label' => 'Create Post'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form['name']->getData();
            $description = $form['description']->getData();
            $category = $form['category']->getData();
            $post->setName($name);
            $post->setDescription($description);
            $post->setPublicDate(new \DateTime());
            $post->setCategory($category);
            $this->em->persist($post);
            $this->em->flush();

            // Add a message
            $this->addFlash('notice', 'Post added');

            return $this->redirectToRoute('feed_list');
        }

        return $this->render('feedsreader/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/edit/{id}", name="feed_edit")
     */
    public function editAction($id, Request $request)
    {
        $post = $this->em->getRepository('AppBundle:Post')->find($id);

        $form = $this->createFormBuilder($post)
            ->add('name', TextType::class, ['attr' => ['form-control']])
            ->add('description', TextareaType::class, ['attr' => ['form-control']])
            ->add('category', EntityType::class, [
                'class' => 'AppBundle:Category',
                'choice_label' => 'name',
            ])
            ->add('save', SubmitType::class, ['label' => 'Update Post'])
            ->getForm();
        $form->setData($post);
        $category = $this->em->getRepository('AppBundle:Category')->find($post->getCategory());
        $form->get('category')->setData($category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form['name']->getData();
            $description = $form['description']->getData();
            $category = $form['category']->getData();
            $post->setName($name);
            $post->setDescription($description);
            $post->setCategory($category);
            $this->em->persist($post);
            $this->em->flush();

            // Add a message
            $this->addFlash('notice', 'Post Updated');

            return $this->redirectToRoute('feed_list');
        }
        return $this->render('feedsreader/edit.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/delete/{id}", name="feed_delete")
     */
    public function deleteAction($id, Request $request)
    {
        $post = $this->em->getRepository("AppBundle:Post")->find($id);
        $this->em->remove($post);
        $this->em->flush();
        $this->addFlash('notice', 'Post removed');

        return $this->redirectToRoute('feed_list');
    }

    /**
     * @Route("/category/", name="feedcategory_list")
     */
    public function listCategoryAction(Request $request)
    {
        $categories = $this->em->getRepository("AppBundle:Category")->findAll();

        return $this->render('feedscategory/index.html.twig', ['categories' => $categories]);
    }

    /**
     * @Route("/category/create/", name="feedcategory_create")
     */
    public function createCategoryAction(Request $request)
    {
        $category = new Category;

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class, ['attr' => ['form-control']])
            ->add('save', SubmitType::class, ['label' => 'Create Category'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form['name']->getData();
            $category->setName($name);
            $this->em->persist($category);
            $this->em->flush();

            // Add a message
            $this->addFlash('notice', 'Category added');

            return $this->redirectToRoute('feedcategory_list');
        }

        return $this->render('feedscategory/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/category/edit/{id}", name="feedcategory_edit")
     */
    public function editCategoryAction($id, Request $request)
    {
        $category = $this->em->getRepository('AppBundle:Category')->find($id);

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class, ['attr' => ['form-control']])
            ->add('save', SubmitType::class, ['label' => 'Update Category'])
            ->getForm();
        $form->setData($category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form['name']->getData();
            $category->setName($name);
            $this->em->persist($category);
            $this->em->flush();

            // Add a message
            $this->addFlash('notice', 'Category Updated');

            return $this->redirectToRoute('feedcategory_list');
        }
        return $this->render('feedscategory/edit.html.twig', ['form' => $form->createView()]);
    }
}
