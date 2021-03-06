<?php session_start();
//$_SESSION['id']=1;
  // require composer autoload (load all my libraries)
  require 'vendor/autoload.php';
  
  // require my models
  require 'models/connexion_bdd.php';
  require 'models/User.php';
  require 'models/Category.php';
  require 'models/Element.php';
  require 'models/Top.php';
  require 'models/Vote.php';
  //require 'models/inscription.php';

  // Slim initialisation
  $app = new \Slim\Slim(array(
    'view' => '\Slim\LayoutView', // I activate slim layout component
    'layout' => 'layouts/main.php' // I define my main layout
  ));

  // hook before.router, now $app is accessible in my views
  $app->hook('slim.before.router', function () use ($app) {
    $app->view()->setData('app', $app);
  });

  // views initiatilisation
  $view = $app->view();
  $view->setTemplatesDirectory('views');

  // GET /
  $app->get('/', function() use ($app) {
    $tops = Top::get_best_tops();
	 $last_tops = Top::get_last_tops();
  if(isset($_SESSION['id'])){
    $top_user_follow = Top::get_tops_follows($_SESSION['id']);
    $app->render( 
      'accueil.php', 
      array("tops" => $tops, "last_tops" => $last_tops, "top_user_follow"=>$top_user_follow) 
    );
  }else{
    $app->render( 
      'accueil.php', 
      array("tops" => $tops, "last_tops" => $last_tops) 
    );
  }
  })->name('root'); // named route so I can use with "urlFor" method

  //POST /root connexion
  $app->post('/', function() use ($app) {
    $_SESSION['id']=User::connexion($_POST['pseudo'],$_POST['password']);
    if($_SESSION['id']!=false){
    	$tops = Top::get_best_tops();
    	$last_tops = Top::get_last_tops();
      $top_user_follow = Top::get_tops_follows($_SESSION['id']);
      $app->flashNow('success', 'Connexion réussie !');
        $app->render(
          'accueil.php',
    		array("tops" => $tops, "last_tops" => $last_tops, "top_user_follow"=>$top_user_follow));
    }
    else {
      $app->flashNow('error', 'Erreur lors de la connexion, veuillez vérifier votre pseudo et votre mot de passe');
      $app->render(
      'users/connexion.php');
    }
  })->name('root_connexion');
 

///USERS////
 
  // GET /users-:user_id
  $app->get('/users-:user_id', function ($id) use ($app) {
    $user = User::getUser($id);
    $nbrTops = User::count_top($id);
    $followers=User::get_followers($id);
    $app->render(
      'users/show.php', 
      array("user" => $user, "followers"=> $followers, "tops"=> $nbrTops)
    );
  })->name('user');
  
  //POST /users-:user_id
  $app->post('/update_user', function () use ($app) {
  // Si le formulaire est rempli
    if(isset($_POST['mail']) AND $_POST['mail']!="" AND isset($_POST['password']) AND $_POST['password']!="" AND $_POST['password']==$_POST['password2']) 
      User::updateUser($_POST);
    if($_FILES['avatar']!=null)
      User::upload_image();
    $user = User::getUser($_POST['id']);
    $followers=User::get_followers($_POST['id']);
    $nbrTops = User::count_top($_POST['id']);
    $app->flashNow('success',"Votre profil a bien été mis à jour !");
    $app->render(
      'users/profil.php', 
      array("user" => $user, "followers"=> $followers, "tops"=> $nbrTops)
    );
  })->name('user_update');
  

  // GET /users-:user_id/tops
  $app->get('/users-:user_id/tops', function ($id) use ($app) {
    $tops = Top::get_top_byUser($id);
    $user = User::getUser($id);
    $app->render(
      'users/top-byUser.php', 
      array("tops" => $tops, "user"=>$user)
    );
  });
  
    // GET /users
  $app->get('/users', function() use ($app) {
    $users = User::all();
    $app->render( 
      'users/show_all.php', 
      array("users" => $users) 
    );
  })->name('users');
  
  //GET /account
  $app->get('/account', function () use ($app) {
    $user = User::getUser($_SESSION['id']);
    $nbrTops = User::count_top($_SESSION['id']);
    $followers=User::get_followers($_SESSION['id']);
    $app->render(
      'users/profil.php',
     array("user" => $user, "followers"=> $followers, "tops"=> $nbrTops)
    );
  })->name('account');
  
  //post /account
  $app->post('/account', function () use ($app) {
    $id = User::inscription($_POST['pseudo'],$_POST['password'],$_POST['password2'],$_POST['mail']); 
    $user = User::getCurrentUser($id);
    $nbrTops = User::count_top($id);
    $followers=User::get_followers($id);
    $app->flashNow('success',"Inscription réussie ! Tu peux maintenant modifier ton profil, créer, suivre et partager des tops !");
    $app->render(
      'users/profil.php',
     array("user" => $user, "followers"=> $followers, "tops"=> $nbrTops));
  })->name('account_create');

  //GET /connexion
  $app->get('/connexion', function () use ($app) {
    $app->render(
      'users/connexion.php' 
    );
  })->name('connexion'); 
 
   //GET /deconnexion
  $app->get('/deconnexion', function () use ($app) {
	session_destroy();
	$_SESSION=[];
    $app->render(
      'users/connexion.php' 
    );
  })->name('deconnexion');
 
   //GET /inscription
  $app->get('/inscription', function () use ($app) {

    session_destroy();
    $app->render(
      'users/inscription.php' 
    );
  })->name('inscription');
  
 ////TOPS////
 
   //GET /tops
  $app->get('/tops', function () use ($app) {
    $tops = Top::get_all_top();
    $categories = Category::get_all_category();
    $app->render(
      'tops/show_all.php',  
      array("tops" => $tops, "categories"=>$categories) 
    );
  })->name('tops');

  //GET /tops/:top_id
  $app->get('/tops-:top_id', function ($id) use ($app) {
    $top = Top::get_top($id);
    $likes= Top::get_likes($id);
    $followers= User::get_followers($top[0]['user_id']);
    $app->render(
      'tops/afficherTop.php',
      array("top" => $top, "likes" => $likes, "followers" => $followers)
    );
  });

  $app->get('/tops/category-:cat_id', function ($cat_id) use ($app) {
    $tops = Top::get_top_byCategory($cat_id);
    $categories = Category::get_all_category();
    $app->render(
      'tops/top_byCategory.php',  
      array("tops" => $tops, "categories"=>$categories) 
    );
  });

     //GET /top-add
	$app->get('/top-add', function () use ($app) {
    if (isset($_SESSION['id']) && isset($_SESSION['pseudo'])) {
      $categories = Category::get_all_category();
      $app->render(
        'tops/top_creation.php',
        array("categories" => $categories)
      );
    }
    else { 
      $app->flashNow('error',"Veuillez vous connecter pour accéder à ce contenu.");
      $app->render(
      'layouts/blank.php');
    }
    })->name('top-add');

   //POST /tops-add-element
	$app->post('/tops-add-elements', function () use ($app) {
    if (isset($_SESSION['id']) && isset($_SESSION['pseudo'])) {
    $tops = Top::add_top($_POST['title'], $_POST['description'], $_POST['category'], $_SESSION['id']);
    $categories = Category::get_all_category();
    $app->render(
      'tops/element_creation.php',
      array("top_id" => $tops, "categories" => $categories)
    );
    }
    else { 
      $app->flashNow('error',"Veuillez vous connecter pour accéder à ce contenu.");
      $app->render(
      'layouts/blank.php');
    }
  })->name('creation_elements');
  


    //POST/top-creat
  $app->post('/my-new-top', function () use ($app) {
    $element_id[] = Element::add_element($_POST['title'], $_POST['description'] );
    if($_FILES['image']!=null){
      Element::upload_image($element_id);}

    $vote = Vote::add_vote( $_POST['emplacement'], $_SESSION['id'], $element_id, $_POST['top_id'] );
    $top = Top::get_top($_POST['top_id'] );

    $app->render(
      'tops/afficherTop.php',
      array("element_id" => $element_id, "vote"=>$vote, "top"=>$top)
    );
  })->name('top_creat');
     

  $app->get('/retop-:top_id', function ($id) use ($app) {
      $top = Top::get_top($id);
      $app->render(
        'tops/retop.php',
        array("top" => $top)
      );
    });


    $app->post('/retop/elements', function () use ($app) {
      $tops = Top::add_top($_POST['title'], $_POST['description'], $_POST['category'], $_SESSION['id'], $_POST['source_id']);
      $app->render(
        'tops/retop-element.php',
        array("top_id" => $tops)
      );  
    });



  //GET /tops/search
  $app->get('/search', function () use ($app) {
    $app->render(
      'tops/search.php'
    );
  })->name('search');

  $app->post('/search', function () use ($app) {
    $resTop = Top::search($_POST['mot']);
    $resUser = User::search($_POST['mot']);
    if($resTop==null && $resUser==null)
      $app->flashNow('error',"Aucun résultat n'a été obtenu pour cette recherche");
    $app->render(
      'tops/search.php',
      array("resTop" => $resTop, "resUser" => $resUser)
    );
  })->name('post_search');

  //IMAGES CARREES
  $app->get('/image/:path', function ($path) {
      
    // TODO lancer la fonction de redimensionnement de l'image
    
    // TODO rÃ©cupÃ©rer le bon format d'image (png, jpeg ou gif) cf: http://fr.wikipedia.org/wiki/Type_MIME
    $formatImage = 'jpeg';
    
    // application du bon content type
    $app->response->headers['Content-Type'] = 'image/' . $formatImage;
  });

  // always need to be at the bottom of this file !
  $app->run();