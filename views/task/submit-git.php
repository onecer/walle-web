<?php
/**
 * @var yii\web\View $this
 */
$this->title = yii::t('task', 'submit task title');
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\Project;
use app\models\Task;

?>
<link rel="stylesheet" href="/dist/css/bootstrap-treeview.min.css">
<div class="box">
    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
      <div class="box-body">
        <?= $form->field($task, 'title')->label(yii::t('task', 'submit title'), ['class' => 'control-label bolder blue']) ?>

        <!-- 分支选取 -->
        <?php if ($conf->repo_mode == Project::REPO_MODE_BRANCH) { ?>
          <div class="form-group">
              <label><?= yii::t('task', 'select branches') ?>
                  <a class="show-tip icon-refresh green" href="javascript:;"></a>
                  <span class="tip"><?= yii::t('task', 'all branches') ?></span>
                  <i class="get-branch icon-spinner icon-spin orange bigger-125" style="display: none"></i>
              </label>
              <select name="Task[branch]" aria-hidden="true" tabindex="-1" id="branch" class="form-control select2 select2-hidden-accessible">
                  <option value="master">master</option>
              </select>
          </div>
        <?php } ?>
        <!-- 分支选取 end -->

        <?= $form->field($task, 'commit_id')->dropDownList([])
          ->label(yii::t('task', 'select branch').'<i class="get-history icon-spinner icon-spin orange bigger-125"></i>', ['class' => 'control-label bolder blue']) ?>

          <!-- 全量/增量 -->
          <div class="form-group">
              <label class="text-right bolder blue">
                  <?= yii::t('task', 'file transmission mode'); ?>
              </label>
              <div id="transmission-full-ctl" class="radio" style="display: inline;" data-rel="tooltip" data-title="<?= yii::t('task', 'file transmission mode full tip') ?>" data-placement="right">
                  <label>
                      <input name="Task[file_transmission_mode]" value="<?= Task::FILE_TRANSMISSION_MODE_FULL ?>" checked="checked" type="radio" class="ace">
                      <span class="lbl"><?= yii::t('task', 'file transmission mode full') ?></span>
                  </label>
              </div>

              <div id="transmission-part-ctl" class="radio" style="display: inline;" data-rel="tooltip" data-title="<?= yii::t('task', 'file transmission mode part tip') ?>" data-placement="right">
                  <label>
                      <input name="Task[file_transmission_mode]" value="<?= Task::FILE_TRANSMISSION_MODE_PART ?>" type="radio" class="ace">
                      <span class="lbl"><?= yii::t('task', 'file transmission mode part') ?></span>
                  </label>
              </div>
          </div>
          <!-- 全量/增量 end -->
          <!-- 加载版本之间 -->
          <div class="form-group" id="commit_between_sel" style="display:none;">
              <label class="control-label bolder blue" for="task-commit_start_id">选择Commit号比较差异文件</label>
              <div class="row">
                  <div class="col-sm-1">开始ID：</div>
                  <div class="col-sm-4"><select id="task-commit_start_id" class="form-control" name="Task[commit_start_id]"></select></div>
                  <div class="col-sm-1">结束ID：</div>
                  <div class="col-sm-4"><select id="task-commit_end_id" class="form-control" name="Task[commit_end_id]"></select></div>
                  <div class="col-sm-2"><button type="button" class="btn btn-primary select-node" id="btn-load-commits-files">查看列表</button></div>
              </div>
          </div>
          <!-- 加载版本之间 end -->
          <!-- 控制区 -->
          <div class="row" style="display: none;" id="task-part_update">
              <div class="col-sm-6">
                  <div class="row"><label class="control-label bolder blue" style="display: inline-block;">搜索更改文件</label></div>
                  <div class="row">
                      <div class="row">
                          <div class="col-sm-6">
                              <div class="form-group">
                                  <label for="input-select-node" class="sr-only">Search Tree:</label>
                                  <input type="input" class="form-control input-lg" id="input-select-node" placeholder="关键词" value="">
                              </div>
                          </div>
                          <div class="col-sm-2">
                              <div class="form-group">
                                  <button type="button" class="btn btn-success select-node" id="btn-select-node">选定</button>
                              </div>
                          </div>
                          <div class="col-sm-2">
                              <div class="form-group">
                                  <button type="button" class="btn btn-danger select-node" id="btn-unselect-node">取消</button>
                              </div>
                          </div>
                          <div class="col-sm-2">
                              <div class="form-group">
                                  <button type="button" class="btn btn-primary select-node" id="btn-add-selected" >添加选定</button>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="row"><label class="control-label bolder blue" style="display: inline-block;">更改文件目录树</label></div>
                  <div class="row">
                      <div id="treeview-selectable" class=""></div>
                  </div>

              </div>
              <div class="col-sm-6">
                  <!-- 文件列表 -->
                  <?= $form->field($task, 'file_list')
                      ->textarea([
                          'rows'           => 20,
                          'placeholder'    => "index.php\nREADME.md\ndir_name\nfile*",
                          'data-html'      => 'true',
                          'data-placement' => 'top',
                          'data-rel'       => 'tooltip',
                          'data-title'     => yii::t('task', 'file list placeholder'),
                          'style'          => 'display: none',
                      ])
                      ->label(yii::t('task', 'file list'),
                          ['class' => 'control-label bolder blue', 'style' => 'display: none']) ?>
              </div>
          </div><!-- /.box-body -->

      <div class="box-footer">
        <input type="submit" class="btn btn-primary" value="<?= yii::t('w', 'submit') ?>">
      </div>

    <!-- 错误提示-->
    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="800px">
                <div class="modal-header">
                    <button type="button" class="close"
                            data-dismiss="modal" aria-hidden="true">
                        &times;
                    </button>
                    <h4 class="modal-title" id="myModalLabel">
                        <?= yii::t('w', 'modal error title') ?>
                    </h4>
                </div>
                <div class="modal-body"></div>
            </div><!-- /.modal-content -->
        </div>

    </div>
    <!-- 错误提示-->

    <?php ActiveForm::end(); ?>
</div>
<script src="/dist/js/bootstrap-treeview.min.js"></script>
<script>
    var $selectableTree;
    var initSelectableTree;
    var findSelectableNodes;
    var selectableNodes;


    // Select/unselect
    $('#input-select-node').on('keyup', function (e) {
        selectableNodes = findSelectableNodes();
        $('.select-node').prop('disabled', !(selectableNodes.length >= 1));
    });

    $('#btn-select-node.select-node').on('click', function (e) {
        $selectableTree.treeview('selectNode', [ selectableNodes, { silent: true }]);
    });

    $('#btn-unselect-node.select-node').on('click', function (e) {
        $selectableTree.treeview('unselectNode', [ selectableNodes, { silent: true }]);
    });

    // 递归拼接节点路径
    function getPathByNodeId(nodeId){
        var nodeObj = $selectableTree.treeview('getParent',nodeId);
        if("text" in nodeObj){
            var filePath = nodeObj.text;
            if(nodeObj.parentId!==undefined){
                filePath = getPathByNodeId(nodeObj.nodeId) + '/' + filePath;
            } else {
                return filePath;
            }
        }
        return filePath;
    }

    // 添加选中项到右边列表
    $('#btn-add-selected.select-node').on('click', function (e) {
        var selectData = $selectableTree.treeview('getSelected');
        var filelists="";
//        var selectJson = eval(JSON.stringify(selectData));
        for(var o in selectData){
//            if("parentId" in selectJson[o]){
            if(selectData[o].parentId!==undefined){
                filelists = filelists + getPathByNodeId(selectData[o].nodeId) + "/" +selectData[o].text + "\n";
            }else{
                filelists = filelists + selectData[o].text + "\n";
            }
        }
        $('#task-file_list').val(filelists);
    });

</script>
<script type="text/javascript">
    jQuery(function($) {
        // 用户上次选择的分支作为转为分支
        var project_id = <?= (int)$_GET['projectId'] ?>;
        var branch_name = 'pre_branch_' + project_id;
        var pre_branch = ace.cookie.get(branch_name);
        if (pre_branch) {
            var option = '<option value="' + pre_branch + '" selected>' + pre_branch + '</option>';
            $('#branch').html(option)
        }

        function getBranchList() {
            $('.get-branch').show();
            $('.tip').hide();
            $('.show-tip').hide();
            $.get("<?= Url::to('@web/walle/get-branch?projectId=') ?>" + <?= (int)$_GET['projectId'] ?>, function (data) {
                // 获取分支失败
                if (data.code) {
                    showError(data.msg);
                }
                var select = '';
                $.each(data.data, function (key, value) {
                    // 默认选中 master 分支
                    var checked = value.id == 'master' ? 'selected' : '';
                    select += '<option value="' + value.id + '"' + checked + '>' + value.message + '</option>';
                });
                $('#branch').html(select);
                $('.get-branch').hide();
                $('.show-tip').show();
                if(data.data.length == 1 || ace.cookie.get(branch_name) != 'master') {
                    // 获取分支完成后, 一定条件重新获取提交列表
                    $('#branch').change();
                }

            });
        }

        // 获取两次commit之间更新的内容 by yyy
        $("#btn-load-commits-files").click(function(event) {
            $.get("<?= Url::to('@web/walle/get-commit-file-json?projectId=') ?>" + project_id + "&start="+
                $('#task-commit_start_id').val()+"&end=" + $('#task-commit_end_id').val() + "&branch=" + $('#branch').val(), function (data) {
                // 获取失败
                if (data.code) {
                    showError(data.msg);
                }
                tree = data.data;
                initSelectableTree = function() {
                    return $('#treeview-selectable').treeview({
                        data: tree,
                        "emptyIcon":"icon-file-alt",
                        "expandIcon":"icon-folder-close-alt",
                        "collapseIcon":"icon-folder-open-alt",
                        multiSelect: true
                    });
                };
                $selectableTree = initSelectableTree();
                findSelectableNodes = function() {
                    return $selectableTree.treeview('search', [$('#input-select-node').val(), {
                        ignoreCase: false,
                        exactMatch: false
                    }]);
                }
                selectableNodes = findSelectableNodes();
            });
        });

        function getCommitBetweenList() {
            // 还需获取最新记录和最后提交的记录
            $.get("<?= Url::to('@web/walle/get-commit-file-json?projectId=') ?>" + project_id + "&start="+
                $('#task-commit_start_id').val()+"&end=" + $('#task-commit_end_id').val() + "&branch=" + $('#branch').val(), function (data) {
                // 获取失败
                if (data.code) {
                    showError(data.msg);
                }
                tree = data.data;
                initSelectableTree = function() {
                    return $('#treeview-selectable').treeview({
                        data: tree,
                        "emptyIcon":"icon-file-alt",
                        "expandIcon":"icon-folder-close-alt",
                        "collapseIcon":"icon-folder-open-alt",
                        multiSelect: true
                    });
                };
                $selectableTree = initSelectableTree();
                findSelectableNodes = function() {
                    return $selectableTree.treeview('search', [$('#input-select-node').val(), {
                        ignoreCase: false,
                        exactMatch: false
                    }]);
                }
                selectableNodes = findSelectableNodes();
            });
        }

        function getCommitList() {
            $('.get-history').show();
            $.get("<?= Url::to('@web/walle/get-commit-history?projectId=') ?>" + <?= (int)$_GET['projectId'] ?> +"&branch=" + $('#branch').val(), function (data) {
                // 获取commit log失败
                if (data.code) {
                    showError(data.msg);
                }

                var select = '';
                $.each(data.data, function (key, value) {
                    select += '<option value="' + value.id + '">' + value.message + '</option>';
                });
                $('#task-commit_id').html(select);
                $('#task-commit_start_id').html(select);
                $('#task-commit_end_id').html(select);
                $('.get-history').hide()
                // 页面加载完默认拉取项目最后一次commit和最新一次commit之间的文件列表
                //getCommitBetweenList();
            });
        }

        $('#branch').change(function() {
            // 添加cookie记住最近使用的分支名字
            ace.cookie.set(branch_name, $(this).val(), 86400*30)
            getCommitList();
        });

        // 页面加载完默认拉取master的commit log
        getCommitList();

        // 查看所有分支提示
        $('.show-tip')
            .hover(
            function() {
                $('.tip').show()
            },
            function() {
                $('.tip').hide()
            })
            .click(function() {
                getBranchList();
            });

        // 错误提示
        function showError($msg) {
            $('.modal-body').html($msg);
            $('#myModal').modal({
                backdrop: true,
                keyboard: true,
                show: true
            });
        }

        // 清除提示框内容
        $("#myModal").on("hidden.bs.modal", function () {
            $(this).removeData("bs.modal");
        });

        // 公共提示
        $('[data-rel=tooltip]').tooltip({container:'body'});
        $('[data-rel=popover]').popover({container:'body'});

        // 切换显示文件列表
        $('body').on('click', '#transmission-full-ctl', function() {
//            $('#task-part_block').hide();
            $('#task-part_update').hide();
            $('#task-file_list').hide();
            $('label[for="task-file_list"]').hide();
            $('#commit_between_sel').hide();
        }).on('click', '#transmission-part-ctl', function() {
//            $('#task-part_block').show();
            $('#task-part_update').show();
            $('#task-file_list').show();
            $('label[for="task-file_list"]').show();
            $('#commit_between_sel').show();
        });

    })

</script>
