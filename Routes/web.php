<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
Route::prefix('routing')->group(function() {
    Route::get('/', 'RoutingController@index');
});
*/

// admin 접속 uri prefix
$prefixAdmin = config('jiny.admin.setting.prefix');
if(!$prefixAdmin) $prefixAdmin = "admin";

Route::middleware(['web'])
->name('admin.')
->prefix($prefixAdmin)->group(function () {
    Route::resource('routes',\Modules\Routing\Http\Controllers\RouteController::class);
});


/** ----- ----- ----- ----- -----
 * Dynamic route
 */

if(!function_exists("jinyRoute")) {
    function jinyRoute($uri) {
        $row = DB::table('jiny_route')
        ->where('enable',true)
        ->where('route',$_SERVER['PATH_INFO'])->first();
        return $row;
    }
}


function jinyRouteParser($type)
{
    if($type == "view") {
        Route::get($_SERVER['PATH_INFO'],[
            Modules\Pages\Http\Controllers\PageView::class,
            "index"
        ]);
    } else if($type == "markdown") {
        Route::get($_SERVER['PATH_INFO'],[
            Modules\Pages\Http\Controllers\MarkdownView::class,
            "index"
        ]);
    } else if($type == "post") {
        Route::get($_SERVER['PATH_INFO'],[
            Modules\Pages\Http\Controllers\PostView::class,
            "index"
        ]);
    } else if($type == "table") {
    } else if($type == "form") {
    }
}

// 페이지 라우트 검사.
if(isset($_SERVER['PATH_INFO'])) {

    if($row = jinyRoute($_SERVER['PATH_INFO'])) {
        $uris = explode('/', $_SERVER['PATH_INFO']);

        //livewire 통신은 제외
        if($uris[1] != "livewire") {
            Route::middleware(['web'])
                ->name( str_replace("/",".",$_SERVER['PATH_INFO']).".")
                ->group(function () use ($row){

                    $type = parserKey($row->type);
                    jinyRouteParser($type);

                });

        }
    } else {
        //jiny_route 미등록
        // actions 폴더 검사
        $path = resource_path('actions');

        $filename = str_replace("/","_",$_SERVER['PATH_INFO']).".json";
        $filename = ltrim($filename,"_");
        if(file_exists($path.DIRECTORY_SEPARATOR.$filename)) {
            $json = file_get_contents($path.DIRECTORY_SEPARATOR.$filename);
            $actions = json_decode($json,true);

            if(isset($actions['view_content'])) {
                // static view
                DB::table("jiny_route")->insertOrIgnore([
                    'route'=>$_SERVER['PATH_INFO'],
                    'type'=>"view:view",
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                Route::middleware(['web'])
                ->name( str_replace("/",".",$_SERVER['PATH_INFO']).".")
                ->group(function () use ($row){
                    jinyRouteParser("view");
                });

            } else if(isset($actions['post_table'])) {
                // post
                DB::table("jiny_route")->insertOrIgnore([
                    'route'=>$_SERVER['PATH_INFO'],
                    'type'=>"post:post",
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                Route::middleware(['web'])
                ->name( str_replace("/",".",$_SERVER['PATH_INFO']).".")
                ->group(function () use ($row){
                    jinyRouteParser("post");
                });
            } else if(isset($actions['view_markdown'])) {
                // post
                DB::table("jiny_route")->insertOrIgnore([
                    'route'=>$_SERVER['PATH_INFO'],
                    'type'=>"markdown:markdown",
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                Route::middleware(['web'])
                ->name( str_replace("/",".",$_SERVER['PATH_INFO']).".")
                ->group(function () use ($row){
                    jinyRouteParser("markdown");
                });
            } else if(isset($actions['table'])) {

            }
        }

    }
}
