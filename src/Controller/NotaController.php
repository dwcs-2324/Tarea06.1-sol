<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotaRepository;
use App\Entity\Nota;
use App\OptionsResolver\NotaOptionsResolver;

use InvalidArgumentException;


#[Route("/api", "api_")]

class NotaController extends AbstractController
{
    // #[Route('/nota', name: 'app_nota')]
    #[Route('/notas', name: 'notas', methods: ["GET"])]
    public function index(NotaRepository $notaRepository): JsonResponse
    {
        $notas = $notaRepository->findAll();
        return $this->json($notas);

        // return $this->json([
        //     'message' => 'Welcome to your new controller!',
        //     'path' => 'src/Controller/NotaController.php',
        // ]);
    }


    #[Route("/notas/{id}", "get_nota", methods: ["GET"])]
    public function getNota(Nota $nota): JsonResponse
    {
        return $this->json($nota);
    }


    #[Route("/notas", "create_nota", methods: ["POST"])]
    public function createNota(
        Request $request,
        NotaRepository $notaRepository,
        ValidatorInterface $validator
        ,
        NotaOptionsResolver $notaOptionsResolver
    ): JsonResponse {

        try {
            $requestBody = json_decode($request->getContent(), true);
            $fields = $notaOptionsResolver->configureTitle(true)->resolve($requestBody);
            $nota = new Nota();
            $nota->setTitle($requestBody["title"]);
            // To validate the entity
            $errors = $validator->validate($nota);
            if (count($errors) > 0) {
                throw new InvalidArgumentException((string) $errors);
            }

            //++tuve que añadir yo a mano el método save en el repository
            $notaRepository->save($nota, true);
            //$todoRepository->getEntityManager()->persist($todo);
            return $this->json($nota, status: Response::HTTP_CREATED);
        }
        //++ \Exception con scope global
        catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    #[Route("/notas/{id}", "delete_nota", methods: ["DELETE"])]
    public function deleteNota(Nota $nota, NotaRepository $notaRepository)
    {
        $notaRepository->remove($nota, true);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route("/notas/{id}", "update_nota", methods: ["PATCH", "PUT"])]
    public function updateNota(Nota $nota, Request $request, NotaOptionsResolver $notaOptionsResolver, ValidatorInterface $validator,
     EntityManagerInterface $em)
    {
        try {
            $requestBody = json_decode($request->getContent(), true);
           
            $isPutMethod = $request->getMethod() === "PUT";
            $fields = $notaOptionsResolver
                ->configureTitle($isPutMethod)
                ->configureCompleted($isPutMethod)
                ->resolve($requestBody);
    
                foreach($fields as $field => $value) {
                    switch($field) {
                        case "title":
                            $nota->setTitle($value);
                            break;
                        case "completed":
                            $nota->setCompleted($value);
                            break;
                    }
                }
                $errors = $validator->validate($nota);
                if (count($errors) > 0) {
                    throw new InvalidArgumentException((string) $errors);
                }
        
                $em->flush();
        
                return $this->json($nota);
    //++
        } catch(\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}