<?php

namespace App\Controllers;
use App\Database;
use App\Exceptions\FormValidationException;
use App\Redirect;
use App\Validation\FormValidator;

class ArticlesCommentsController
{
    public function store(array $vars): Redirect
    {

        $articleId = (int) $vars['articleId'];
        try {
            $validator = new FormValidator($_POST, [
                'text' => ['required']
            ]);
            $validator->passes();

            $alreadyCommented = Database::connection()
                ->createQueryBuilder()
                ->select('*')
                ->from('article_comments')
                ->where('article_id = ?')
                ->andWhere('user_id = ?')
                ->setParameter(0, $articleId)
                ->setParameter(1, $_SESSION['current_user']['user_id'])
                ->executeQuery()
                ->fetchOne();

            if (empty($alreadyCommented)) {
                Database::connection()
                    ->insert('article_comments', [
                        'user_id' => $_SESSION['current_user']['user_id'],
                        'article_id' => $articleId,
                        'text' => $_POST['text']
                    ]);
            }

        } catch (FormValidationException $exception) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['inputs'] = $_POST;
        }
        return new Redirect('/articles/' . $articleId);
    }

    public function delete(array $vars): Redirect
    {
        $articleId = (int) $vars['articleId'];
        $commentId = (int) $vars['id'];

        $whoCommented = Database::connection()
            ->createQueryBuilder()
            ->select('user_id')
            ->from('article_comments')
            ->where('article_id = ?')
            ->andWhere('id = ?')
            ->setParameter(0, $articleId)
            ->setParameter(1, $commentId)
            ->executeQuery()
            ->fetchAllAssociative();

        if($whoCommented[0]['user_id'] == $_SESSION['current_user']['user_id']){
            Database::connection()
                ->delete('article_comments', [
                    'id' => $commentId
                ]);
        }

        return new Redirect('/articles/' . $articleId);
    }
}