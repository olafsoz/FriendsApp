<?php

namespace App\Controllers;
use App\Database;
use App\Exceptions\FormValidationException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Article;
use App\Models\Comment;
use App\Redirect;
use App\Validation\FormValidator;
use App\Validation\Errors;
use App\View;
use Exception;

class ArticlesController
{
    public function index(): View
    {
        $articlesQuery = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('articles')
            ->orderBy('created_at', 'desc')
            ->executeQuery()
            ->fetchAllAssociative();

        $articles = [];

        foreach ($articlesQuery as $articleData)
        {
            $articles[] = new Article(
                $articleData['title'],
                $articleData['description'],
                $articleData['created_at'],
                $articleData['user_id'],
                $articleData['id']
            );
        }

        if (isset($_SESSION['current_user'])) {
            return new View('Articles/index', [
                'articles' => $articles,
                'session' => $_SESSION['current_user']['name']
            ]);
        } else {
            return new View('Articles/index', [
                'articles' => $articles,
            ]);
        }
    }

    public function show(array $vars): View
    {
        $articlesQuery = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('articles')
            ->where('id = ?')
            ->setParameter(0, $vars['id'])
            ->executeQuery()
            ->fetchAssociative();


        $article = new Article(
            $articlesQuery['title'],
            $articlesQuery['description'],
            $articlesQuery['created_at'],
            $articlesQuery['user_id'],
            $articlesQuery['id']
        );

        // search for who created article, the name

        $articlePoster = Database::connection()
            ->createQueryBuilder()
            ->select('name')
            ->from('user_profiles')
            ->where('user_id = ?')
            ->setParameter(0, $articlesQuery['user_id'])
            ->executeQuery()
            ->fetchAssociative();

        // Get comments

        $commentsQuery = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('article_comments')
            ->where('article_id = ?')
            ->setParameter(0, (int)$vars['id'])
            ->orderBy('created_at', 'desc')
            ->executeQuery()
            ->fetchAllAssociative();

        $comments = [];


        // search for commenter name

        for ($i = 0; $i < count($commentsQuery); $i++) {
            $realName = Database::connection()
                ->createQueryBuilder()
                ->select('name')
                ->from('user_profiles')
                ->where('user_id = ?')
                ->setParameter(0, (int)$commentsQuery[$i]['user_id'])
                ->executeQuery()
                ->fetchAllAssociative();

            $comments[] = new Comment(
              $commentsQuery[$i]['article_id'],
              $realName[0]['name'] ?? 'User no longer exists',
              $commentsQuery[$i]['text'],
              $commentsQuery[$i]['created_at'],
              $commentsQuery[$i]['id']
            );
        }

        // make select query for article likes
        $articleLikes = Database::connection()
            ->createQueryBuilder()
            ->select('COUNT(id)')
            ->from('article_likes')
            ->where('article_id = ?')
            ->setParameter(0, (int)$vars['id'])
            ->executeQuery()
            ->fetchOne();

        return new View('Articles/show', [
            'article' => $article,
            'session' => $_SESSION['current_user']['name'],
            'article_likes' => (int)$articleLikes,
            'comments' => $comments,
            'articlePoster' => $articlePoster['name'] ?? 'User no longer exists',
            'errors' => $_SESSION['errors'] ?? []
        ]);
    }

    public function create(): View
    {
        if (isset($_SESSION['current_user'])) {
            return new View('Articles/create', [
                'errors' => Errors::getAll(),
                'inputs' => $_SESSION['inputs'] ?? [],
                'session' => $_SESSION['current_user']['name']
            ]);
        } else {
            return new View('Articles/create', [
                'errors' => Errors::getAll(),
                'inputs' => $_SESSION['inputs'] ?? []
            ]);
        }
    }

    public function store(): Redirect
    {
        try {
            $validator = new FormValidator($_POST, [
                'title' => ['required', 'min:3'],
                'description' => ['required']
            ]);
            $validator->passes();

            Database::connection()
                ->insert('articles', [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'user_id' => $_SESSION['current_user']['user_id']
                ]);
            // redirect /article
            return new Redirect('/articles');
        } catch (FormValidationException $exception) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['inputs'] = $_POST;

            return new Redirect('/articles/create');
        }
    }

    public function delete(array $vars): Redirect
    {
        $articleId = (int)$vars['id'];

        //can only delete if you made it

        $whoCreated = Database::connection()
            ->createQueryBuilder()
            ->select('user_id')
            ->from('articles')
            ->where('id = ?')
            ->setParameter(0, $articleId)
            ->executeQuery()
            ->fetchAssociative();

        if($whoCreated['user_id'] == $_SESSION['current_user']['user_id']) {
            Database::connection()
                ->delete('articles', [
                    'id' => $articleId
                ]);
        }

        return new Redirect('/articles');
    }

    public function edit(array $vars): View
    {
        try {
            $articlesQuery = Database::connection()
                ->createQueryBuilder()
                ->select('*')
                ->from('articles')
                ->where('id = ?')
                ->setParameter(0, $vars['id'])
                ->executeQuery()
                ->fetchAssociative();

            if (!$articlesQuery) {
                throw new ResourceNotFoundException("Article with id {$vars['id']} not found.");
            }

            $article = new Article(
                $articlesQuery['title'],
                $articlesQuery['description'],
                $articlesQuery['created_at'],
                $articlesQuery['user_id'],
                $articlesQuery['id']
            );

            if (isset($_SESSION['current_user'])) {
                return new View('Articles/edit', [
                    'article' => $article,
                    'session' => $_SESSION['current_user']['name']
                ]);
            } else {
                return new View('Articles/edit', [
                    'article' => $article,
                ]);
            }

        } catch (ResourceNotFoundException $exception) {
            return new View('404');
        }
    }

    public function update(array $vars): Redirect
    {
        Database::connection()->update('articles', [
            'title' => $_POST['title'],
            'description' => $_POST['description']
        ], ['id' => (int)$vars['id']]);

        return new Redirect('/articles');
    }

    public function like(array $vars): Redirect
    {
        $articleId = (int)$vars['id'];

        // make select query, check if user already liked

        $alreadyLiked = Database::connection()
            ->createQueryBuilder()
            ->select('user_id')
            ->from('article_likes')
            ->where('article_id = ?')
            ->andWhere('user_id = ?')
            ->setParameter(0, $articleId)
            ->setParameter(1, $_SESSION['current_user']['user_id'])
            ->executeQuery()
            ->fetchOne();

        if (empty($alreadyLiked)) {
            Database::connection()->insert('article_likes', [
                'article_id' => $articleId,
                'user_id' => $_SESSION['current_user']['user_id']
            ]);
        }
        return new Redirect("/articles/{$articleId}");

    }
}