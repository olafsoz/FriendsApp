<?php

namespace App\Controllers;
use App\Database;
use App\Redirect;
use App\View;

class UsersController {
    public function register(): Redirect
    {
        $emailExists = Database::connection()
            ->createQueryBuilder()
            ->select('email')
            ->from('users')
            ->where('email = ?')
            ->setParameter(0, $_POST['email'])
            ->executeQuery()
            ->fetchAllAssociative();

        if ($_POST['name'] !== "" && $_POST['surname'] !== "" && $_POST['birthday'] !== "" &&
            $_POST['email'] !== "" && $_POST['password'] !== "" && empty($emailExists)) {
            Database::connection()
                ->insert('users', [
                    'email' => $_POST['email'],
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
                ]);

            $id = Database::connection()
                ->createQueryBuilder()
                ->select('id')
                ->from('users')
                ->where('email = ?')
                ->setParameter(0, $_POST['email'])
                ->executeQuery()
                ->fetchAssociative();

            Database::connection()
                ->insert('user_profiles', [
                    'user_id' => $id['id'],
                    'name' => $_POST['name'],
                    'surname' => $_POST['surname'],
                    'birthday' => $_POST['birthday']
                ]);
            $_SESSION['current_user'] = [
                'user_id' => $id['id'],
                'name' => $_POST['name'],
                'surname' => $_POST['surname']
            ];
            return new Redirect('/articles');
        } else {
            return new Redirect('/users/register');
        }
    }
    public function showRegister(): View
    {
        return new View('Users/register');
    }
    public function login()
    {
        $info = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('users')
            ->where('email = ?')
            ->setParameter(0, $_POST['email'])
            ->executeQuery()
            ->fetchAssociative();
        if (password_verify($_POST['password'], $info['password'])) {
            $user = Database::connection()
                ->createQueryBuilder()
                ->select('*')
                ->from('user_profiles')
                ->where('user_id = ?')
                ->setParameter(0, $info['id'])
                ->executeQuery()
                ->fetchAssociative();
            $_SESSION['current_user'] = [
                'user_id' => $user['user_id'],
                'name' => $user['name'],
                'surname' => $user['surname']
            ];
            return new Redirect('/articles');
        } else {
            return new Redirect('/');
        }
    }

    public function check()
    {
//        var_dump($_POST);die;
//        $_SESSION['email'] = $_POST
    }

    public function start(): Redirect
    {
        session_destroy();
        return new Redirect('/users/login');
    }

    public function showLogin(): View
    {
        return new View('Users/login');
    }

    public function showAllUsers(): View
    {
        $userInfo = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('user_profiles')
            ->orderBy('user_id', 'asc')
            ->executeQuery()
            ->fetchAllAssociative();

        $emailAndUser = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('users')
            ->orderBy('id', 'asc')
            ->executeQuery()
            ->fetchAllAssociative();
//        echo "<pre>";
//        var_dump($emailAndUser);die;

        return new View('Users/people', [
            'userInfo' => $userInfo,
            'emailAndUser' => $emailAndUser
        ]);
    }
    public function addFriend(array $vars): Redirect
    {
        $userId = (int) $vars['userId'];
        if ($userId == $_SESSION['current_user']['user_id']) return new Redirect('/articles');
        $alreadyRequested = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('friends')
            ->where('requester = ?')
            ->andWhere('receiver = ?')
            ->setParameter(0, $_SESSION['current_user']['user_id'])
            ->setParameter(1, $userId)
            ->executeQuery()
            ->fetchAssociative();

        foreach ($_SESSION['friends'] as $fr) {
            [$me, $you] = $fr;
            if ([$_SESSION['current_user']['user_id'], $userId] == [$me, $you]) {
                return new Redirect('/articles');
            }
        }
        if (empty($alreadyRequested)) {
            Database::connection()
                ->insert('friends', [
                    'requester' => $_SESSION['current_user']['user_id'],
                    'receiver' => $userId
                ]);
        }

        return new Redirect('/articles');
    }
    public function friendRequests(): View
    {
        $request = Database::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('friends')
            ->where('receiver = ?')
            ->setParameter(0, $_SESSION['current_user']['user_id'])
            ->executeQuery()
            ->fetchAllAssociative();
//        echo "<pre>";
//        var_dump($request);die;


        if (!empty($request)) {
            foreach ($request as $rq) {
                $name[] = Database::connection()
                    ->createQueryBuilder()
                    ->select('name')
                    ->from('user_profiles')
                    ->where('user_id = ?')
                    ->setParameter(0, $rq['requester'])
                    ->executeQuery()
                    ->fetchAssociative();
            }
        }
        return new View('Users/friendRequests', [
            'friendRequest' => $name ?? [],
            'count' => count($request)
        ]);
    }
    public function friends(): View
    {
        $iAdded = Database::connection()
            ->createQueryBuilder()
            ->select('receiver')
            ->from('friends')
            ->where('requester = ?')
            ->orderBy('receiver', 'asc')
            ->setParameter(0, $_SESSION['current_user']['user_id'])
            ->executeQuery()
            ->fetchAllAssociative();

        $a1 = [];
        foreach ($iAdded as $h) {
            $a1[] = $h['receiver'];
        }

        $someoneElseAdded = Database::connection()
            ->createQueryBuilder()
            ->select('requester')
            ->from('friends')
            ->where('receiver = ?')
            ->orderBy('requester', 'asc')
            ->setParameter(0, $_SESSION['current_user']['user_id'])
            ->executeQuery()
            ->fetchAllAssociative();

        $a2 = [];
        foreach ($someoneElseAdded as $h) {
            $a2[] = $h['requester'];
        }

//        echo "<pre>";
//        var_dump($iAdded);
//        var_dump($someoneElseAdded);die;

        $friend = [];
        if (!empty($a1) && !empty($a2)) {
            for ($i = 0; $i < count($a2); $i++) {
                if (isset($a2[$i])) {
                    $key = array_search($a2[$i], $a1);
                    $friend[] = $a1[$key];
                    $_SESSION['friends'] = [$_SESSION['current_user']['user_id'], $a1[$key]];
                }
            }
        }
//        var_dump($friend);die;
        for ($k = 0; $k < count($friend); $k++) {
            $name = Database::connection()
                ->createQueryBuilder()
                ->select('name')
                ->from('user_profiles')
                ->where('user_id = ?')
                ->setParameter(0, $friend[$k])
                ->executeQuery()
                ->fetchAssociative();
            $friendName[] = $name['name'];
        }

        return new View('Users/friends', [
            'friends' => $friendName ?? []
        ]);
    }
}
