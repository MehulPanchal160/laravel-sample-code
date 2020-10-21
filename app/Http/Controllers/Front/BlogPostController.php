<?php

namespace App\Http\Controllers\Front;

use App\BlogCategories;
use App\BlogPosts;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;

class BlogPostController extends FrontController {

    public function __construct() {
        $this->apiData = new \stdClass();
    }

    /**
     * Get blog post
     * @return object
     */
    public function getBlogPosts(Request $request) {

        $search = Input::get('search', '');
        $limit = (isset($request->limit) && $request->limit != '') ? $request
                ->limit : 0;
        $catAlias = (isset($request->catAlias) && $request->catAlias != '' &&
                $request->catAlias != 'undefined') ?
                $request->catAlias : '';
        $tagName = (isset($request->tagName) && $request->tagName != '' &&
                $request->tagName != 'undefined') ?
                $request->tagName : '';
        $page_no = (isset($request->page_no) && $request->page_no != '') ?
                $request->page_no : 1;
        $results = BlogPosts::

                select(DB::raw("blog_posts.id, blog_posts.read_time, blog_posts.name, blog_posts.alias, blog_posts.image, blog_posts.description, blog_posts.created_at, blog_categories.name AS category_name, users.first_name, users.last_name"))
                ->leftJoin('blog_post_categories', 'blog_posts.id', '=', 'blog_post_categories.blog_post_id')
                ->leftJoin('blog_categories', 'blog_post_categories.blog_category_id', '=', 'blog_categories.id')
                ->leftJoin('users', 'blog_posts.created_by', '=', 'users.id')->where('blog_posts.status', 1);

        if ($catAlias) {
            $results->where('blog_categories.alias', $catAlias)->where('blog_categories.status', 1);
        } elseif ($search) {
            $results->where(function ($query) use ($search) {
                $query->Where('blog_posts.name', 'like', "%$search%");
                $query->orWhere('blog_posts.description', 'like', "%$search%");
            });
        } elseif ($tagName) {
            $results->whereRaw('FIND_IN_SET(?,blog_posts.tags)', $tagName);
        }
        $results->orderby('blog_posts.created_at', 'desc');

        $total = $results->count();
        $result = $results->get();

        foreach ($result as $k => $each_result) {
            $each_result->author = $each_result->first_name . ' ' .
                    $each_result
                    ->last_name;
            $each_result->image_url = $this->getImageUrl($each_result->image, 'blog_posts');
            $each_result->post_date = $each_result->created_at
                    ->format('D, d M Y');
            $each_result->day = $each_result->created_at
                    ->format('d');
            $each_result->month = $each_result->created_at
                    ->format('M');
            $each_result->likeCount = $each_result->userLikes->count();
            $each_result->commentCount = $each_result->comments->count();

            $num_chars_name = 23;
            $num_chars_desc = 80;
            if ($page_no == 1 && $k == 0) {
                $num_chars_name = 60;
                $num_chars_desc = 385;
            }
            $each_result->name = str_limit($each_result->name, $num_chars_name);
            $each_result->description = str_limit($each_result->description, $num_chars_desc);
        }

        $this->apiData->blogpostslist = $result;
        $this->apiData->featuredBlogs = $this->getFeaturedBlogs(3);
        $this->apiData->count = $total;
        $this->apiMessage = "Blog Posts";
        $this->apiCode = 200;
        $this->apiStatus = true;
        return $this->response();
    }
    
    /**
     * Get  featured blogs
     * @param int $limit
     * @return array
     */
    public function getFeaturedBlogs($limit = 3) {
        $featuredBlogs = array();
        $blogs = BlogPosts::where('is_featured', 1)->where('status', 1)->orderBy('id', 'desc')->limit($limit)->get();
        if ($blogs->isNotEmpty()) {
            foreach ($blogs as $blog) {
                $featuredBlogs[] = array(
                    'name' => $blog->name,
                    'alias' => $blog->alias,
                    'image' => $this->getImageUrl($blog->image, 'blog_posts'),
                    'like' => $blog->userLikes->count(),
                    'comment' => $blog->comments->count(),
                    'author' => $blog->createdBy->first_name . ' ' . $blog->createdBy->last_name,
                    'date' => date("jS F Y", strtotime($blog->created_at))
                );
            }
        }
        return $featuredBlogs;
    }

    public function getPostDetail(Request $request) {
        $postAlias = (isset($request->postAlias) && $request->postAlias != '') ? $request->postAlias : '';

        $results = BlogPosts::

                select(DB::raw("blog_posts.id, blog_posts.name, blog_posts.alias, blog_posts.description, blog_posts.secondary_cat_ids, blog_posts.tags, blog_posts.image, blog_posts.meta_title, blog_posts.meta_description, blog_posts.created_at, blog_categories.name AS category_name, blog_categories.alias AS categoryAlias, users.first_name, users.last_name, users.photo, users.job_profile"))
                ->leftJoin('blog_post_categories', 'blog_posts.id', '=', 'blog_post_categories.blog_post_id')
                ->leftJoin('blog_categories', 'blog_post_categories.blog_category_id', '=', 'blog_categories.id')
                ->leftJoin('users', 'blog_posts.created_by', '=', 'users.id')
                ->where('blog_posts.alias', $postAlias)
                ->where('blog_posts.status', 1)
                ->orderby('blog_posts.created_at', 'desc');

        $total = $results->count();
        $result = $results->first();
        if ($result) {
            $result->comments;
            $result->photo = $this->getImageUrl($result->photo, 'profile_photos');
            $result->secondary_cat_ids = ($result->secondary_cat_ids ? explode(',', $result->secondary_cat_ids) : []);
            $result->tags = ($result->tags ? explode(',', $result->tags) : []);
            $primary_blog = $result->category_name;
            $sec_blog = $result->secondary_cat_ids;
            $blogCategoryObj = BlogCategories::select("name", "alias")
                            ->whereIn('id', $sec_blog)->get();
        } else {
            $this->apiMessage = "No records found.";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        $results_post = BlogPosts::

                        select(DB::raw("blog_posts.id, blog_posts.read_time, blog_posts.name, blog_posts.alias, blog_posts.description, blog_posts.secondary_cat_ids, blog_posts.tags, blog_posts.image, blog_posts.meta_title, blog_posts.meta_description, blog_posts.created_at, blog_categories.name AS category_name, users.first_name, users.last_name, users.photo"))
                        ->leftJoin('blog_post_categories', 'blog_posts.id', '=', 'blog_post_categories.blog_post_id')
                        ->leftJoin('blog_categories', 'blog_post_categories.blog_category_id', '=', 'blog_categories.id')
                        ->leftJoin('users', 'blog_posts.created_by', '=', 'users.id')
                        ->where('blog_posts.alias', '<>', $result->alias)->where('blog_posts.status', 1)->orderby('blog_posts.created_at', 'desc')->get();

        foreach ($results_post as $k => $each_result) {
            $each_result->author = $each_result->first_name . ' ' .
                    $each_result
                    ->last_name;
            $each_result->image_url = $this->getImageUrl($each_result->image, 'blog_posts');
            $each_result->post_date = $each_result->created_at
                    ->format('D, d M Y');
            $each_result->day = $each_result->created_at
                    ->format('d');
            $each_result->month = $each_result->created_at
                    ->format('M');
            $each_result->likeCount = $each_result->userLikes->count();
            $each_result->commentCount = $each_result->comments->count();
        }

        $data = [
            'related' => $results_post->where('category_name', $primary_blog)->values(),
            'new' => $results_post,
            'popular' => $results_post->where('likeCount', '>', 0)->sortByDesc('likeCount')->values()
        ];

        $this->apiData->postdetail = $result;
        $this->apiData->postList = $data;
        $this->apiData->count = $total;
        $this->apiData->secondaryCategory = $blogCategoryObj;
        $this->apiMessage = "Posts detail";
        $this->apiCode = 200;
        $this->apiStatus = true;
        $this->apiData->featuredBlogs = $this->getFeaturedBlogs(3);
        return $this->response();
    }

    /**
     * Like/dislike blog and return count.
     * @return object
     */
    public function likeBlog(Request $request) {
        $user = Auth::user();
        if ($request->alias && $user) {
            $blog = BlogPosts::where('alias', $request->alias)->where('status', 1)->first();
            if ($blog->userLikes()->where('user_id', $user->id)->exists()) {
                $blog->userLikes()->detach($user->id);
            } else {
                $blog->userLikes()->attach($user->id);
            }
            $this->apiData = $blog->userLikes->count();
            $this->apiMessage = "Like/Dislike";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "Something went wrong. Please try again.";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

    /**
     * Save Comment
     * @param Request $request
     * @return object
     */
    public function saveComment(Request $request) {
        $rules = [
            'alias' => 'required',
            'name' => 'required',
            'email' => 'required',
            'comment' => 'required',
            'follow_up_notify' => 'required',
            'new_posts_notify' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $this->apiMessage = $validator->errors()->all();
            $this->apiCode = 500;
            $this->apiStatus = false;
        } else {
            $blog = BlogPosts::where('alias', $request->alias)->where('status', 1)->first();
            if ($blog->comments()->create($request->all())) {
                $this->apiMessage = 'Comment Successfully Added.';
                $this->apiCode = 200;
                $this->apiStatus = true;
            } else {
                $this->apiMessage = 'Comment not added successfully.';
                $this->apiCode = 500;
                $this->apiStatus = false;
            }
        }
        return $this->response();
    }

}
