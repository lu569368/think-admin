<?php
namespace app\service\model\rbac;

use app\service\validate\rbac\RuleValidate;
use think\Model;

/**
 * 规则模型
 */
class RuleModel extends Model
{
    protected $table = 'pg_auth_rule';

    protected $pk = 'id';

    /**
     * 数据验证
     * @param array $data
     * @param string $scene
     */
    public static function validate($data, $scene)
    {
        $validate = new RuleValidate;

        if (!$validate->scene($scene)->check($data)) {
            exception($validate->getError());
        }
    }

    /**
     * 添加规则
     * @param array $data
     */
    public function add(array $data)
    {
        try {
            self::validate($data, 'create');
            $this->save($data);
        } catch(\Exception $e) {
            exception($e->getMessage());
        }
    }

    /**
     * 修改规则
     * @param array $data
     */
    public function edit(int $id, array $data)
    {
        try {
            $info = $this->getInfo($id);
            self::validate($data, 'update');
            $info->save($data);
        } catch (\Exception $e) {
            exception($e->getMessage());
        }
    }

    /**
     * 删除规则
     * @param string|int $id
     */
    public function deleteRule($id)
    {
        try {
            $info = $this->getInfo($id);

            // 检查是否有子规则
            $res = $this->getChild($info->id);
            
            if (!empty($res)) {
                exception('请删除子规则');
            }
            
            $info->delete();
        } catch (\Exception $e) {
            exception($e->getMessage());
        }
    }

    /**
     * 获取树形结构
     *
     */
    public function getTree()
    {
        $data = $this->order('pid asc')->select();
        $category = new \lib\Category(array('id','pid','title','cname'));
        $res = $category->getTree($data);//获取分类数据树结构
        return $res;
    }

    /**
     * 获取规则详情
     * @param int|string $id
     *
     */
    public function getInfo($id)
    {
        $res = $this->getOrFail($id);

        return $res;
    }

    /**
     * 获取子规则
     * @param string|int $pid
     * @return array
     */
    protected function getChild($pid)
    {
        $data = $this->order('pid asc')->select();
        $category = new \lib\Category(array('id','pid','title','cname'));
        $data = $category->getChild($pid, $data);

        return $data;
    }

    public function getList()
    {
        $top = $this->where('pid', 0)->select();
        $data = $this->where('pid', '<>', 0)->select();

        foreach($top as $key=>$v) {
            $actions = $this->getActions($data, $v['id']);
            $top[$key]['action'] = [];
            $top[$key]['permissionId'] = $v['role'];
            if (!empty($actions)) {
                $top[$key]['action'] = $actions;
            }
        }

        return $top;
    }

    protected function getActions($data, $pid, &$temp = [])
    {
        foreach($data as $v) {
            if ($v['pid'] == $pid) {
                $temp[] = [
                    'role' => $v['role'],
                    'title' => $v['title'],
                    'name' => $v['name'],
                    'pid' => $v['pid'],
                    'id' => $v['id'],
                    'value' => $v['id'],
                    'label' => $v['title'],
                    'status' => $v['status']
                ];
                $temp = array_merge($temp, $this->getActions($data, $v['id']));
            }
        }

        return $temp;
    }
}
