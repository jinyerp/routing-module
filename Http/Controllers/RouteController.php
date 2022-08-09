<?php

namespace Modules\Routing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

use Jiny\Table\Http\Controllers\ResourceController;
class RouteController extends ResourceController
{
    public function __construct()
    {
        parent::__construct();
        $this->setVisit($this);

        ##
        //$this->actions['table'] = "jiny_route"; // 테이블 정보
        //$this->actions['paging'] = 10; // 페이지 기본값

        //$this->actions['view_list'] = "jinyadmin::jiny.route.list";
        //$this->actions['view_form'] = "jinyadmin::jiny.route.form";

    }


    /**
     * DB 갱신전에 호출되는 동작
     */
    public function hookUpdating($form)
    {
        // 코드명 변경 체크
        if ($this->wire->old['route'] != $form['route']) {
            $path = resource_path('actions');
            $filename = $path.$this->wire->old['route'].".json";
            if(file_exists($filename)) {
                // 파일명 변경하기
                $newfile = str_replace("/","_",ltrim($form['route'],"/")).".json";
                rename($filename, $path.DIRECTORY_SEPARATOR.$newfile);
            }
        }

        return $form;
    }


    /**
     * DB 데이터를 삭제하기 전에 동작
     */
    public function hookDeleting($row)
    {
        $path = resource_path('actions');
        $filename = $path.$row->route.".json";
        if(file_exists($filename)) {
            unlink($filename);
        }

        return $row;
    }


}
