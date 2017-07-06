<?php
/* *****************************************************************
 * @Author: wushuiyong
 * @Created Time : 日  8/ 2 10:43:15 2015
 *
 * @File Name: command/Git.php
 * @Description:
 * *****************************************************************/
namespace app\components;

use app\models\Project;
use app\models\Task as TaskModel;
use yii\helpers\StringHelper;

class Git extends Command {

    /**
     * 更新仓库
     *
     * @param string $branch
     * @param string $gitDir
     * @return bool|int
     */
    public function updateRepo($branch = 'master', $gitDir = null) {
        $gitDir = $gitDir ?: Project::getDeployFromDir();
        $dotGit = rtrim($gitDir, '/') . '/.git';
        // 存在git目录，直接pull
        if (file_exists($dotGit)) {
            $cmd[] = sprintf('cd %s ', $gitDir);
            $cmd[] = sprintf('/usr/bin/env git checkout -q %s', $branch);
            $cmd[] = sprintf('/usr/bin/env git fetch -p -q --all');
            $cmd[] = sprintf('/usr/bin/env git reset -q --hard origin/%s', $branch);
            $command = join(' && ', $cmd);
            return $this->runLocalCommand($command);
        }
        // 不存在，则先checkout
        else {
            $cmd[] = sprintf('mkdir -p %s ', $gitDir);
            $cmd[] = sprintf('cd %s ', $gitDir);
            $cmd[] = sprintf('/usr/bin/env git clone -q %s .', $this->getConfig()->repo_url);
            $cmd[] = sprintf('/usr/bin/env git checkout -q %s', $branch);
            $command = join(' && ', $cmd);
            return $this->runLocalCommand($command);
        }
    }

    /**
     * 更新到指定commit版本
     *
     * @param TaskModel $task
     * @return bool
     */
    public function updateToVersion(TaskModel $task) {
        // 先更新
        $destination = Project::getDeployWorkspace($task->link_id);
        $this->updateRepo($task->branch, $destination);
        $cmd[] = sprintf('cd %s ', $destination);
        $cmd[] = sprintf('/usr/bin/env git reset -q --hard %s', $task->commit_id);
        $command = join(' && ', $cmd);

        return $this->runLocalCommand($command);
    }

    /**
     * 获取分支列表
     *
     * @return array
     */
    public function getBranchList() {
        $destination = Project::getDeployFromDir();
        // 应该先更新，不然在remote git删除当前选中的分支后，获取分支列表会失败
        $this->updateRepo('master', $destination);
        $cmd[] = sprintf('cd %s ', $destination);
        $cmd[] = '/usr/bin/env git fetch -p';
        $cmd[] = '/usr/bin/env git pull -a';
        $cmd[] = '/usr/bin/env git branch -a';
        $command = join(' && ', $cmd);
        $result = $this->runLocalCommand($command);
        if (!$result) {
            throw new \Exception(\yii::t('walle', 'get branches failed') . $this->getExeLog());
        }

        $history = [];
        $list = explode(PHP_EOL, $this->getExeLog());
        foreach ($list as &$item) {
            $item = trim($item);
            $remotePrefix = 'remotes/origin/';
            $remoteHeadPrefix = 'remotes/origin/HEAD';

            // 只取远端的分支，排除当前分支
            if (strcmp(substr($item, 0, strlen($remotePrefix)), $remotePrefix) === 0
                && strcmp(substr($item, 0, strlen($remoteHeadPrefix)), $remoteHeadPrefix) !== 0) {
                $item = substr($item, strlen($remotePrefix));
                $history[] = [
                    'id'      => $item,
                    'message' => $item,
                ];
            }
        }

        return $history;
    }

    /**
     * 获取提交历史
     *
     * @param string $branch
     * @param int $count
     * @return array
     * @throws \Exception
     */
    public function getCommitList($branch = 'master', $count = 20) {
        // 先更新
        $destination = Project::getDeployFromDir();
        $this->updateRepo($branch, $destination);
        $cmd[] = sprintf('cd %s ', $destination);
        $cmd[] = '/usr/bin/env git log -' . $count . ' --pretty="%h - %an %s" ';
        $command = join(' && ', $cmd);
        $result = $this->runLocalCommand($command);
        if (!$result) {
            throw new \Exception(\yii::t('walle', 'get commit log failed') . $this->getExeLog());
        }

        $history = [];
        // 总有一些同学没有团队协作意识，不设置好编码：(
        $log = htmlspecialchars(GlobalHelper::convert2Utf8($this->getExeLog()));
        $list = explode(PHP_EOL, $log);
        foreach ($list as $item) {
            $commitId = substr($item, 0, strpos($item, '-') - 1);
            $history[] = [
                'id'      => $commitId,
                'message' => $item,
            ];
        }
        return $history;
    }

    /**
     * 获取tag记录
     *
     * @return array
     */
    public function getTagList($count = 20) {
        // 先更新
        $this->updateRepo();
        $destination = Project::getDeployFromDir();
        $cmd[] = sprintf('cd %s ', $destination);
        $cmd[] = '/usr/bin/env git tag -l ';
        $command = join(' && ', $cmd);
        $result = $this->runLocalCommand($command);
        if (!$result) {
            throw new \Exception(\yii::t('walle', 'get tags failed') . $this->getExeLog());
        }

        $history = [];
        $list = explode(PHP_EOL, $this->getExeLog());
        foreach ($list as $item) {
            $history[] = [
                'id'      => $item,
                'message' => $item,
            ];
        }
        return $history;
    }

    /**
     * 获取commits之间的文件
     * @param $branch
     * @param $star
     * @param $end
     * @return array
     * @throws \Exception
     * @author YYY <ldh@qq.com>
     */
    public function getFileBetweenCommits($branch, $star, $end){
        // 先更新
        $destination = Project::getDeployFromDir();
        $this->updateRepo($branch, $destination);
        $cmd[] = sprintf('cd %s',$destination);
        $cmd[] = sprintf('/usr/bin/env git checkout %s',$branch);
        $cmd[] = sprintf('/usr/bin/env git diff %s %s --name-only', $star, $end);
        $command = join(' && ', $cmd);
        $result = $this->runLocalCommand($command);
//        var_dump($command,$this->getExeLog());exit();
        if (!$result) {
            throw new \Exception(\yii::t('walle', 'get commit log failed') . $this->getExeLog());
        }

        $list = [];
        $files = StringHelper::explode($this->getExeLog(), PHP_EOL);
        $files = array_map(function($item) {
            return trim(substr($item, strpos($item, " ")));
        }, $files);
        // 排除点文件
        if (in_array('.', $files)) {
            unset($files[array_search('.', $files)]);
        }
        foreach ($files as $key => $file) {
            // 如果是目录，则目录下的文件则可以不带了
            if (in_array(dirname($file), $files)) continue;
            $list[] = $file;
        }

        return $list;
    }

    /**
     * 将文件列表制作成json
     * @param $branch
     * @param $star
     * @param $end
     * @return string
     * @author YYY <ldh@qq.com>
     */
    public function getFileBetweenCommitsJson($branch, $star, $end) {
        // 先获取更新列表
        $tree=array();
        $list=$this->getFileBetweenCommits($branch, $star, $end);
        for($i=0;$i<count($list);$i++){
            if(!empty($list[$i])){
                $path_str=array_reverse(explode('/', $list[$i]));
                $tree = $this->_pushNode($tree,$path_str);
            }
        }
        $json = $this->_makeCommitFilesTreeviewJson($tree);
        return json_encode($json);
    }

    /**
     * 递归生成树的数组
     * @param $subtree
     * @param $name
     * @return mixed
     * @author YYY <ldh@qq.com>
     */
    private function _pushNode($subtree,$name){
        if(!empty($name)){
            $tmpname=end($name);
            if(!array_key_exists(end($name),$subtree)){
                $subtree[$tmpname]=array();
            }
            array_pop($name);
            $subtree[$tmpname]=$this->_pushNode($subtree[$tmpname],$name);
            return $subtree;
        }
    }

    /**
     * 根据列表返回json
     * @param $tree
     * @return array
     * @author YYY <ldh@qq.com>
     */
    private function _makeCommitFilesTreeviewJson($tree){
        $json=[];
        if(!empty($tree)){
            foreach ($tree as $key => $value){
                $jsonNode=[];
                $jsonNode['text']=$key;
                if($value!==null){
                    $jsonNode['nodes']=$this->_makeCommitFilesTreeviewJson($value);
                }
                array_push($json,$jsonNode);
            }
        }
        return $json;
    }

}
