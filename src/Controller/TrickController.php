<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Trick;
use App\Form\TrickType;
use App\Repository\TrickRepository;
use App\Service\LoadMoreService;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TrickController extends AbstractController
{
    public function __construct(
        private readonly LoadMoreService $loadMoreService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/trick', name: 'app_trick_list')]
    public function list(TrickRepository $trickRepository): Response
    {
        $tricks = $this->em->getRepository(Trick::class)->findBy([], null, 10);

        return $this->render('trick/list.html.twig', [
            'tricks' => $tricks,
        ]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/tricks/load-more/{offset}', name: 'app_trick_load_more')]
    public function loadMore(int $offset): JsonResponse
    {
        $data = $this->loadMoreService->loadItems(
            Trick::class,
            $offset,
            5,
            'trick/_tricks_list.html.twig',
            'tricks');

        return new JsonResponse($data);
    }

    //    #[Route('/trick/{slug}', name: 'app_trick_detail')]
    //    public function detail(): Response
    //    {
    //        return $this->render('trick/detail.html.twig', [
    //        ]);
    //    }

    /**
     * @throws \Exception
     */
    #[Route('/trick/new', name: 'app_trick_new')]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        TrickRepository $trickRepository,
        SluggerInterface $slugger,
        PictureService $pictureService): Response
    {
        $trick = new Trick();
        $trickForm = $this->createForm(TrickType::class, $trick);
        $trickForm->handleRequest($request);

        if ($trickForm->isSubmitted() && $trickForm->isValid()) {
            $existingTrick = $trickRepository->findOneBy(['name' => $trick->getName()]);
            if ($existingTrick) {
                $this->addFlash('error', 'Une figure avec ce nom existe déjà.');

                return $this->redirectToRoute('app_trick_new');
            }

            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);
            $trick->setUser($this->getUser());
            $em->persist($trick);
            $pictures = $trickForm->get('pictures')->getData();
            if (is_array($pictures)) {
                foreach ($pictures as $picture) {
                    if ($picture instanceof UploadedFile) {
                        $fileName = $pictureService->addPicture($picture, 'trickPictures', 250, 250);
                        $pictureEntity = new Picture();
                        $pictureEntity->setFilename($fileName);
                        $pictureEntity->setTrick($trick);
                        $trick->getPictures()->add($pictureEntity);
                        $em->persist($pictureEntity);
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', 'Le trick a bien été ajouté.');

            return $this->redirectToRoute('app_trick_list');
        }

        return $this->render('trick/create.html.twig', [
            'form' => $trickForm->createView(),
        ]);
    }

    #[Route('/trick/edit', name: 'app_trick_edit')]
    public function edit(Request $request, Trick $trick): Response
    {
        return $this->render('trick/create.html.twig', [
        ]);
    }

    #[Route('/trick/delete', name: 'app_trick_delete')]
    public function delete(Trick $trick): Response
    {
        return $this->redirectToRoute('trick_list');
    }
}
