<?php

namespace Larke\Admin\Frontend\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Larke\Admin\Support\Tree;
use Larke\Admin\Http\Controller as BaseController;
use Larke\Admin\Frontend\Model\Menu as MenuModel;

/**
 * 菜单管理
 *
 * @title 菜单管理
 * @desc 菜单管理
 * @order 650
 * @auth true
 * @slug larke-admin-frontend.menu
 *
 * @create 2020-11-3
 * @author deatil
 */
class Menu extends BaseController
{
    /**
     * 列表
     *
     * @title 菜单列表
     * @desc 菜单列表管理
     * @order 651
     * @auth true
     *
     * @param  Request  $request
     * @param  MenuModel $menuModel
     * @return Response
     */
    public function index(Request $request, MenuModel $menuModel)
    {
        $list = $menuModel->getList();
        
        return $this->success(__('获取成功'), [
            'list' => $list,
        ]);
    }
    
    /**
     * 菜单树
     *
     * @title 菜单树列表
     * @desc 菜单树列表管理
     * @order 652
     * @auth true
     *
     * @param  Request  $request
     * @return Response
     */
    public function indexTree(Request $request, MenuModel $menuModel)
    {
        $result = $menuModel->getList();
        
        $result = collect($result)
            ->sortBy('sort')
            ->toArray();
        
        $Tree = new Tree();
        $list = $Tree
            ->withConfig('parentidKey', 'pid')
            ->withConfig('buildChildKey', 'children')
            ->withData($result)
            ->build(0);
        
        return $this->success(__('获取成功'), [
            'list' => $list,
        ]);
    }
    
    /**
     * 菜单子列表
     *
     * @title 菜单子列表
     * @desc 菜单子列表管理
     * @order 653
     * @auth true
     *
     * @param  Request  $request
     * @return Response
     */
    public function indexChildren(Request $request, MenuModel $menuModel)
    {
        $id = $request->input('id', 0);
        if (is_array($id)) {
            return $this->error(__('父级ID错误'));
        }
        
        $type = $request->input('type');
        
        $result = $menuModel->getList();
        
        $result = collect($result)
            ->sortBy('sort')
            ->toArray();
            
        $Tree = new Tree();
        
        if ($type == 'list') {
            $list = $Tree
                ->withConfig('parentidKey', 'pid')
                ->withConfig('buildChildKey', 'children')
                ->withData($result)
                ->build(0);
            
            $list = $Tree
                ->withConfig('parentidKey', 'pid')
                ->withConfig('buildChildKey', 'children')
                ->buildFormatList($list, $id, 'asc');
        } else {
            $list = $Tree
                ->withConfig('parentidKey', 'pid')
                ->getListChildrenId($result, $id);
        }
        
        return $this->success(__('获取成功'), [
            'list' => $list,
        ]);
    }
    
    /**
     * 创建
     *
     * @title 菜单创建
     * @desc 菜单创建管理
     * @order 654
     * @auth true
     *
     * @param  Request  $request
     * @param  MenuModel $menuModel
     * @return Response
     */
    public function create(Request $request, MenuModel $menuModel)
    {
        $data = $request->all();
        if (empty($data)) {
            return $this->error(__('创建的菜单数据不能为空'));
        }
        
        $insertStatus = $menuModel->insert($data);
        if ($insertStatus === false) {
            return $this->error(__('添加菜单失败'));
        }
        
        return $this->success(__('添加菜单成功'));
    }
    
    /**
     * 更新
     *
     * @title 菜单更新
     * @desc 菜单更新管理
     * @order 655
     * @auth true
     *
     * @param  String $id
     * @param  Request $request
     * @param  MenuModel $menuModel
     * @return Response
     */
    public function update(String $id, Request $request, MenuModel $menuModel)
    {
        if (empty($id)) {
            return $this->error(__('菜单ID不能为空'));
        }
        
        $info = $menuModel->find($id);
        if (empty($info)) {
            return $this->error(__('菜单不存在'));
        }
        
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'pid' => 'required',
            'sort' => 'required',
        ], [
            'pid.required' => __('父级菜单不能为空'),
            'sort.required' => __('排序不能为空'),
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }
        
        // 更新信息
        $status = $menuModel->update($id, $data);
        if ($status === false) {
            return $this->error(__('菜单修改失败'));
        }
        
        return $this->success(__('菜单修改成功'));
    }
    
    /**
     * 删除
     *
     * @title 菜单删除
     * @desc 菜单删除管理
     * @order 656
     * @auth true
     *
     * @param  String $id
     * @param  Request $request
     * @param  MenuModel $menuModel
     * @return Response
     */
    public function delete(String $id, Request $request, MenuModel $menuModel)
    {
        if (empty($id)) {
            return $this->error(__('菜单ID不能为空'));
        }
        
        $info = $menuModel->find($id);
        if (empty($info)) {
            return $this->error(__('菜单不存在'));
        }
        
        $menuChildren = $menuModel->findChildren($id);
        if (! empty($menuChildren)) {
            return $this->error(__('菜单有子菜单，请删除子菜单'));
        }
        
        $status = $menuModel->delete($id);
        if ($status === false) {
            return $this->error(__('菜单删除失败'));
        }
        
        return $this->success(__('菜单删除成功'));
    }
    
    /**
     * 获取全部
     *
     * @title 菜单获取全部
     * @desc 菜单获取全部管理
     * @order 657
     * @auth true
     *
     * @param  Request  $request
     * @return Response
     */
    public function getJson(Request $request, MenuModel $menuModel)
    {
        $json = $menuModel->getFileData();
        
        return $this->success(__('获取成功'), [
            'json' => $json,
        ]);
    }
    
    /**
     * 保存全部
     *
     * @title 菜单保存全部
     * @desc 菜单保存全部管理
     * @order 658
     * @auth true
     *
     * @param  Request  $request
     * @return Response
     */
    public function saveJson(Request $request, MenuModel $menuModel)
    {
        $json = $request->input('json');
        if (empty($json)) {
            return $this->error(__('json不能为空'));
        }
        
        if (empty(json_decode($json, true))) {
            return $this->error(__('json格式错误'));
        }
        
        $status = $menuModel->saveFileData($json);
        if ($status === false) {
            return $this->error(__('保存数据失败'));
        }
        
        return $this->success(__('保存数据成功'));
    }
}
