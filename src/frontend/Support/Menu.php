<?php

namespace Larke\Admin\Frontend\Support;

use Illuminate\Support\Arr;

use Larke\Admin\Support\Tree;
use Larke\Admin\Frontend\Model\Menu as MenuModel;

class Menu
{
    /**
     * Create menu.
     *
     * @return $data array
     * @return $parentId int|string
     * @return $children array
     *
     * @return array
     */
    public static function create($data = [], $parentId = 0) 
    {
        if (empty($data)) {
            return false;
        }
        
        $menuModel = new MenuModel();
        $list = $menuModel->read();
        
        $lastOrder = collect($list)->max('sort');
        
        $menu = $menuModel->insert([
            'pid' => $parentId,
            'sort' => $lastOrder + 1,
            'title' => Arr::get($data, 'title'),
            'url' => Arr::get($data, 'url'),
            'method' => Arr::get($data, 'method'),
            'slug' => Arr::get($data, 'slug'),
        ]);
        
        $children = Arr::get($data, 'children', []);
        foreach ($children as $child) {
            static::create($child, $menu['id']);
        }

        return $menu;
    }

    /**
     * 删除菜单
     * @param string $slug 规则slug
     * @return boolean
     */
    public static function delete($slug)
    {
        $ids = self::getMenuIdsBySlug($slug);
        if (!$ids) {
            return false;
        }
        
        $menuModel = new MenuModel();
        collect($ids)->each(function($id) use($menuModel) {
            $menuModel->delete($id);
        });
        
        return true;
    }

    /**
     * 启用菜单
     * @param string $slug
     * @return boolean
     */
    public static function enable($slug)
    {
        $ids = self::getMenuIdsBySlug($slug);
        if (!$ids) {
            return false;
        }
        
        $menuModel = new MenuModel();
        collect($ids)->each(function($id) use($menuModel) {
            $data = $menuModel->find($id);
            if (! empty($data)) {
                $data['status'] = 1;
            }
            
            $menuModel->update($id, $data);
        });
        
        return true;
    }

    /**
     * 禁用菜单
     * @param string $slug
     * @return boolean
     */
    public static function disable($slug)
    {
        $ids = self::getMenuIdsBySlug($slug);
        if (!$ids) {
            return false;
        }
        
        $menuModel = new MenuModel();
        collect($ids)->each(function($id) use($menuModel) {
            $data = $menuModel->find($id);
            if (! empty($data)) {
                $data['status'] = 0;
            }
            
            $menuModel->update($id, $data);
        });
        
        return true;
    }

    /**
     * 导出指定slug的菜单规则
     * @param string $slug
     * @return array
     */
    public static function export($slug)
    {
        $ids = self::getMenuIdsBySlug($slug);
        if (!$ids) {
            return [];
        }
        
        $menuList = [];
        
        $menuModel = new MenuModel();
        $list = $menuModel->read();
        
        $menu = collect($list)
            ->where('slug', '=', $slug)
            ->toArray();

        if ($menu) {
            $menuList = collect($list)
                ->where('id', 'in', $ids)
                ->sortBy('listorder')
                ->map(function($item) {
                    return [
                        'id' => $item['id'],
                        'pid' => $item['pid'],
                        'slug' => $item['slug'],
                    ];
                })
                ->toArray();
                
            $menuList = Tree::create()
                ->withConfig('buildChildKey', 'children')
                ->withData($menuList)
                ->build($menu['id']);
        }
        
        return $menuList;
    }

    /**
     * 根据slug获取规则IDS
     * @param string $slug
     * @return array
     */
    public static function getMenuIdsBySlug($slug)
    {
        $menuModel = new MenuModel();
        $list = $menuModel->read();
        
        $menu = collect($list)
            ->where('slug', '=', $slug)
            ->toArray();
        
        $idsList = collect($menu)->map(function($menu) use($list) {
            $ids = [];
            if ($menu) {
                $menuId = $menu['id'];
                $menuList = collect($list)
                    ->sortBy('listorder')
                    ->map(function($item) {
                        return [
                            'id' => $item['id'],
                            'pid' => $item['pid'],
                            'slug' => $item['slug'],
                        ];
                    })
                    ->values()
                    ->toArray();
                $ids = Tree::create()
                    ->withConfig('parentidKey', 'pid')
                    ->getListChildrenId($menuList, $menuId);
                $ids[] = $menuId;
            }
            
            return $ids;
        });
        $ids = collect($idsList)->collapse();
        
        return $ids;
    }
    
}
