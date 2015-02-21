<?php

namespace N3rtrivium\EvaluatorPlain;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class App
{
	/**
	 * @var boolean
	 */
	private $debug = false;
	
	/**
	 * @var PDO
	 */
	private $dbh;
	
	/**
	 * @var PDOStatement
	 */
	private $sthTagsByPostId = null;
	
	/**
	 * @var PDOStatement
	 */
	private $sthPosts = null;
	
	/**
	 * @var PDOStatement
	 */
	private $sthUsers = null;
	
	/**
	 * @var PDOStatement
	 */
	private $sthTags = null;
	
	/**
	 * @var PDOStatement
	 */
	private $sthInsertPost = null;

	/**
	 * @var PDOStatement
	 */
	private $sthInsertPostTag = null;
	
	public function __construct(\PDO $dbh, $debug = false)
	{
		$this->dbh = $dbh;
		$this->debug = $debug;
		$this->sthTagsByPostId = $this->dbh->prepare('SELECT t.name FROM post_tag pt LEFT JOIN tags t ON pt.tag_id = t.id WHERE pt.post_id = ?');
		$this->sthPosts = $this->dbh->prepare('SELECT p.id, p.title, p.content, u.name as author_name FROM posts p LEFT JOIN users u ON p.author_id = u.id ORDER BY title DESC');
		$this->sthUsers = $this->dbh->prepare('SELECT u.id, u.name FROM users u ORDER BY name DESC');
		$this->sthTags = $this->dbh->prepare('SELECT t.id, t.name FROM tags t ORDER BY name DESC');
		$this->sthInsertPost = $this->dbh->prepare('INSERT INTO posts (title, content, author_id) VALUES (:title, :content, :author_id)', array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
		$this->sthInsertPostTag = $this->dbh->prepare('INSERT INTO post_tag (post_id, tag_id) VALUES (:post_id, :tag_id)', array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
	}
	
	public function handle()
	{
		$request = Request::createFromGlobals();
		$response = null;
		
		$path = $request->getPathInfo();
		if ($path == '/') {
			$response = $this->index($request);
		}
		
		if ($path == '/gen') {
			$response = $this->generateDemoData($request);
		}
		
		if (!$response) {
			$response = $this->notFound($request);
		}
		
		$response->prepare($request);
		
		$response->send();
	}
	
	public function index(Request $req)
	{
		$this->sthPosts->execute();
		$posts = $this->sthPosts->fetchAll();
		
		$content = '<div>';
		foreach ($posts as $post) {
			$content .= '<hr><div>';			
			$content .= '<h2>'.$post['title'].'</h2>';
			$content .= '<p>'.$post['content'].'<p>';
			$content .= '<p>'.$post['author_name'].'</p>';			
			$this->sthTagsByPostId->execute(array($post['id']));
			$tags = $this->sthTagsByPostId->fetchAll();
			$content .= '<p>';
			foreach ($tags as $tag) {
				$content .= $tag['name'].', ';
			}
			$content .= '</p>';
			$content .= '</div>';
		}
		
		return new Response('<h1>Posts</h1>'.$content);
	}
	
	public function generateDemoData(Request $req)
	{
		
		$this->sthUsers->execute();
		$users = $this->sthUsers->fetchAll();
		$user = $this->selectRandom($users);
		
		$this->sthInsertPost->execute(array(
			':title' => $this->generateRandomString(16),
			':content' => $this->generateRandomString(300),
			':author_id' => $user['id']
		));
		$postId = $this->dbh->lastInsertId();
		
		$this->sthTags->execute();
		$tags = $this->sthTags->fetchAll();
		$firstTag = $this->selectRandom($tags);
		do {
			$secondTag = $this->selectRandom($tags);
		} while ($firstTag['id'] == $secondTag['id']);
		
		
		$this->sthInsertPostTag->execute(array(
			':post_id' => $postId,
			':tag_id' => $firstTag['id']
		));
		
		$this->sthInsertPostTag->execute(array(
			':post_id' => $postId,
			':tag_id' => $secondTag['id']
		));
		
		return new Response('Post '.$postId.' added');
	}
	
	public function notFound(Request $req)
	{
		return new Response('file not found', 404);
	}
	
	private function selectRandom(array $list)
	{
		return $list[rand(0, count($list) - 1)];
	}
	
	private function generateRandomString($length = 10) {
		$characters = '0123456789    abcdefghijkl   mnopqrstuvwxyz    ABCDEFGHIJKLMN   OPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}