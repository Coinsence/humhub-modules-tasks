<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\tasks\models;

use humhub\modules\content\components\ContentContainerPermissionManager;
use humhub\modules\space\modules\manage\models\MembershipSearch;
use humhub\modules\tasks\helpers\TaskUrl;
use humhub\modules\tasks\models\account\TaskAccount;
use humhub\modules\tasks\permissions\CreateTask;
use humhub\modules\tasks\permissions\ProcessUnassignedTasks;
use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\xcoin\helpers\AccountHelper;
use humhub\modules\xcoin\models\Account;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use humhub\modules\notification\models\Notification;
use humhub\modules\tasks\models\checklist\TaskCheckList;
use humhub\modules\tasks\models\checklist\TaskItem;
use humhub\modules\tasks\models\lists\TaskList;
use humhub\modules\tasks\models\scheduling\TaskReminder;
use humhub\modules\tasks\models\scheduling\TaskScheduling;
use humhub\modules\tasks\models\state\TaskState;
use humhub\modules\tasks\models\user\TaskUser;
use humhub\libs\DbDateValidator;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\user\models\User;
use humhub\modules\search\interfaces\Searchable;
use humhub\widgets\Label;
use humhub\modules\tasks\widgets\WallEntry;
use humhub\modules\tasks\permissions\ManageTasks;


/**
 * This is the model class for table "task".
 *
 * The followings are the available columns in table 'task':
 * @property integer $id
 * @property string $title
 * @property string $color
 * @property string $description
 * @property integer $review
 * @property integer $request_sent
 * @property integer $scheduling
 * @property integer $all_day
 * @property string $start_datetime
 * @property string $end_datetime
 * @property integer $status
 * @property integer $cal_mode
 * @property integer $task_list_id
 * @property string $time_zone The timeZone this entry was saved, note the dates itself are always saved in app timeZone
 *
 * @property TaskReminder[] $taskReminder
 * @property TaskItem[] $items
 * @property TaskList $list
 * @property User[] $taskResponsibleUsers
 * @property User[] $taskAssignedUsers
 */
class Task extends ContentActiveRecord implements Searchable
{

    const SCENARIO_EDIT = 'edit';

    /**
     * @inheritdocs
     */
    public $canMove = true;

    /**
     * @inheritdocs
     */
    public $moduleId = 'tasks';

    /**
     * @inheritdocs
     */
    public $wallEntryClass = WallEntry::class;

    public $assignedUsers;
    public $responsibleUsers;
    public $selectedReminders;
    public $newItems;
    public $editItems;

    /**
     * Status
     */
    const STATUS_PENDING = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_PENDING_REVIEW = 3;
    const STATUS_COMPLETED = 5;
    const STATUS_ALL = 4;

    /**
     * @deprecated
     */
    const STATUS_OPEN = 1;

    /**
     * @var array all given statuses as array
     */
    public static $statuses = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_COMPLETED
    ];

    /**
     * User Types
     */
    const USER_ASSIGNED = 0;
    const USER_RESPONSIBLE = 1;


    /**
     * Account Type
     */
    const SPACE_ACCOUNT = 0;
    const WORKER_ACCOUNT = 1;

    /**
     * @var TaskState
     */
    public $state;

    /**
     * @var TaskScheduling
     */
    public $schedule;

    /**
     * @var TaskCheckList
     */
    public $checklist;

    public function init()
    {
        $this->schedule = new TaskScheduling(['task' => $this]);
        $this->checklist = new TaskCheckList(['task' => $this]);

        if ($this->status == null) {
            $this->status = static::STATUS_PENDING;
        }
        $this->updateState();

        if (!$this->all_day) {
            $this->all_day = 1;
        }

        parent::init();

    }

    public function afterFind()
    {
        $this->updateState();
        parent::afterFind();
    }

    protected function updateState()
    {
        $this->state = TaskState::getState($this);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_EDIT] = $scenarios[self::SCENARIO_DEFAULT];
        return $scenarios;
    }


    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name == 'deadline') {
            return parent::__get('end_datetime');
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        parent::__set($name, $value);
        if ($name == 'status') {
            $this->updateState();
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return Yii::t('TasksModule.base', 'Task');
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getIcon()
    {
        return 'fa-tasks';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['color'], 'string', 'max' => 7],
            [['start_datetime', 'end_datetime'], 'required', 'when' => function ($model) {
                return $model->scheduling == 1;
            }, 'whenClient' => "function (attribute, value) {
                return $('#task-scheduling').val() == 1;
            }"],
            [['start_datetime'], DbDateValidator::className()],
            [['end_datetime'], DbDateValidator::className()],
            [['all_day', 'scheduling', 'review', 'request_sent'], 'integer'],
            [['cal_mode'], 'in', 'range' => TaskScheduling::$calModes],
            [['assignedUsers', 'description', 'responsibleUsers', 'selectedReminders'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['task_list_id'], 'validateTaskList']
        ];
    }

    public function validateTaskList($attribute, $params)
    {
        if ($this->task_list_id) {
            $taskList = TaskList::findByContainer($this->content->container)->where(['id' => $this->task_list_id]);
            if (!$taskList) {
                $this->addError('task_list_id', Yii::t('TasksModule.base', 'Invalid task list selection.'));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => Yii::t('TasksModule.models_task', 'Title'),
            'color' => Yii::t('TasksModule.models_task', 'Color'),
            'description' => Yii::t('TasksModule.models_task', 'Description'),
            'review' => Yii::t('TasksModule.models_task', 'Review by responsible user required'),
            'request_sent' => Yii::t('TasksModule.models_task', 'Extend deadline request'),
            'scheduling' => Yii::t('TasksModule.models_task', 'Scheduling'),
            'all_day' => Yii::t('TasksModule.models_task', 'All Day'),
            'start_datetime' => Yii::t('TasksModule.models_task', 'Start'),
            'end_datetime' => Yii::t('TasksModule.models_task', 'End'),
            'status' => Yii::t('TasksModule.models_task', 'Status'),
            'cal_mode' => Yii::t('TasksModule.models_task', 'Add schedule to the space calendar'),
            'parent_task_id' => Yii::t('TasksModule.models_task', 'Parent Task'),
            'newItems' => Yii::t('TasksModule.models_task', 'Checklist Items'),
            'editItems' => Yii::t('TasksModule.models_task', 'Checklist Items'),
            'assignedUsers' => Yii::t('TasksModule.models_task', 'Assigned user(s)'),
            'responsibleUsers' => Yii::t('TasksModule.models_task', 'Responsible user(s)'),
            'selectedReminders' => Yii::t('TasksModule.models_task', 'Reminders'),
            'task_list_id' => Yii::t('TasksModule.base', 'List'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return TaskUrl::viewTask($this);
    }

    public function getList()
    {
        return $this->hasOne(TaskList::class, ['id' => 'task_list_id']);
    }

    /**
     * @param ContentContainerActiveRecord $contentContainer
     * @return \humhub\modules\content\components\ActiveQueryContent
     */
    public static function findUnsorted(ContentContainerActiveRecord $contentContainer)
    {
        return static::find()->contentContainer($contentContainer)->where('task_list_id IS NULL')->readable();
    }

    /**
     * @param ContentContainerActiveRecord $contentContainer
     * @return \humhub\modules\content\components\ActiveQueryContent
     */
    public static function findUnsortedCompleted(ContentContainerActiveRecord $contentContainer)
    {
        return static::findUnsorted($contentContainer)->andWhere(['task.status' => Task::STATUS_COMPLETED]);
    }

    /**
     * @param User|null $user
     * @param ContentActiveRecord|null $container
     * @param int $limit
     * @return array|Notification[]|Task[]|\yii\db\ActiveRecord[]
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public static function findUserTasks(User $user = null, $container = null, $limit = 5)
    {
        if (!$user && !Yii::$app->user->isGuest) {
            $user = Yii::$app->user->getIdentity();
        } else if (!$user) {
            return [];
        }

        $query = ($container) ? self::find()->contentContainer($container) : self::find();

        return $query
            ->leftJoin('task_user', 'task.id=task_user.task_id', [])
            ->where(['task_user.user_id' => $user->id])
            ->andWhere(['!=', 'task.status', Task::STATUS_COMPLETED])
            ->orderBy([new Expression('-task.end_datetime DESC')])
            ->readable()
            ->limit($limit)
            ->all();
    }

    public static function findPendingTasks(ContentContainerActiveRecord $container)
    {
        return self::find()
            ->contentContainer($container)
            ->readable()
            ->andWhere(['!=', 'task.status', Task::STATUS_COMPLETED]);
    }

    public static function findReadable(ContentContainerActiveRecord $container)
    {
        return self::find()
            ->contentContainer($container)
            ->orderBy(['task.end_datetime' => SORT_DESC])
            ->readable();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->schedule->beforeSave();
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        foreach (TaskItem::findAll(['task_id' => $this->id]) as $item) {
            $item->delete();
        }

        foreach (TaskUser::findAll(['task_id' => $this->id]) as $taskUser) {
            $taskUser->delete();
        }

        foreach (TaskReminder::findAll(['task_id' => $this->id]) as $taskReminder) {
            $taskReminder->delete();
        }

        foreach (TaskAccount::findAll(['task_id' => $this->id]) as $taskSpaceAccount) {
            $spaceAccount = Account::findOne(['id' => $taskSpaceAccount->account_id]);
            $spaceAccount->delete();

            $taskSpaceAccount->delete();
        }

        return parent::beforeDelete();
    }

    /**
     * Saves new items (if set) and updates items given edititems (if set)
     *
     * @param boolean $insert
     * @param array $changedAttributes
     * @return boolean
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->scenario === self::SCENARIO_EDIT) {
            $oldTaskUsers = $this->taskUsers;

            TaskUser::deleteAll(['task_id' => $this->id]);

            $this->manageSpaceAccount();

            if (!empty($this->assignedUsers)) {
                foreach ($this->assignedUsers as $guid) {
                    $user = User::findOne(['guid' => $guid]);

                    if (!$user) {
                        continue;
                    }

                    $oldAssigned = array_filter($oldTaskUsers, function ($taskUser) use ($user) {
                        /** @var $taskUser TaskUser */
                        return $taskUser->user_id === $user->id && $taskUser->user_type === Task::USER_ASSIGNED;
                    });

                    $this->addTaskAssigned($guid, empty($oldAssigned));
                }
            }
            if (!empty($this->responsibleUsers)) {
                foreach ($this->responsibleUsers as $guid) {
                    $user = User::findOne(['guid' => $guid]);

                    if (!$user) {
                        continue;
                    }

                    $oldResponsible = array_filter($oldTaskUsers, function ($taskUser) use ($user) {
                        /** @var $taskUser TaskUser */
                        return $taskUser->user_id === $user->id && $taskUser->user_type === Task::USER_RESPONSIBLE;
                    });

                    $this->addTaskResponsible($guid, empty($oldResponsible));
                }
            }

            $this->checklist->afterSave($insert);
            $this->schedule->afterSave($insert, $changedAttributes);

            $this->saveNewItems();

        }

        if ($this->list) {
            $this->list->setAttributes(['updated_at' => new Expression('NOW()')]);
        }
    }

    public function afterMove(ContentContainerActiveRecord $container = null)
    {

        foreach ($this->taskUsers as $taskUser) {
            if (!$container->isMember($taskUser->user_id)) {
                $taskUser->delete();
            }
        }

        $this->updateAttributes(['task_list_id' => null]);
    }

    /**
     * Returns an ActiveQuery for all assigned user models of this task.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskAssignedUsers($filterOutResponsibleUsers = false)
    {
        $query = $this->hasMany(User::class, ['id' => 'user_id'])->via('assignedTaskUsers');

        if ($filterOutResponsibleUsers) {
            $responsible = $this->getTaskResponsibleUsers()->select(['id']);
            $query->where(['not in', 'user.id', $responsible]);
        }

        return $query;
    }

    public function isTaskAssigned($user = null)
    {
        if (!$user && !Yii::$app->user->isGuest) {
            $user = Yii::$app->user->getIdentity();
        } else if (!$user) {
            return false;
        }

        $taskAssigned = array_filter($this->assignedTaskUsers, function (TaskUser $p) use ($user) {
            return $p->user_id == $user->id;
        });

        return !empty($taskAssigned);
    }

    public function addTaskAssigned($user, $sendNotification = true)
    {
        $user = (is_string($user)) ? User::findOne(['guid' => $user]) : $user;

        if (!$user) {
            return false;
        }

        if (!$this->isTaskAssigned($user)) {
            $taskAssigned = new TaskUser([
                'task_id' => $this->id,
                'user_id' => $user->id,
                'user_type' => self::USER_ASSIGNED,
                'sendNotificationOnCreation' => $sendNotification,
            ]);
            return $taskAssigned->save();
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        if ($this->list) {
            $this->list->setAttributes(['updated_at' => new Expression('NOW()')]);
        }

        parent::afterDelete();
    }


    // ###########  handle assigned users  ###########

    public function getTaskUsers()
    {
        return $this->hasMany(TaskUser::class, ['task_id' => 'id']);
    }

    /**
     * Returns an ActiveQuery for all assigned task users of this task.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignedTaskUsers()
    {
        return $this->getTaskUsers()->andOnCondition(['user_type' => self::USER_ASSIGNED]);
    }

    public function hasTaskAssigned()
    {
        return !empty($this->assignedTaskUsers);
    }

    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])->via('taskUsers');
    }




    // ###########  handle responsible users  ###########

    /**
     * Returns an ActiveQuery for all responsible task users of this task.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskResponsible()
    {
        return $this->hasMany(TaskUser::class, ['task_id' => 'id'])->andOnCondition(['user_type' => self::USER_RESPONSIBLE]);
    }

    public function hasTaskResponsible()
    {
        return !empty($this->taskResponsible);
    }

    /**
     * Returns an ActiveQuery for all responsible user models of this task.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskResponsibleUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])->via('taskResponsible');
    }

    public function isTaskResponsible($user = null)
    {
        if (!$user && !Yii::$app->user->isGuest) {
            $user = Yii::$app->user->getIdentity();
        } else if (!$user) {
            return false;
        }

        $taskResponsible = array_filter($this->taskResponsible, function (TaskUser $p) use ($user) {
            return $p->user_id == $user->id;
        });

        return !empty($taskResponsible);
    }

    public function isOwner($user = null)
    {
        if (!$user && !Yii::$app->user->isGuest) {
            $user = Yii::$app->user->getIdentity();
        } else if (!$user) {
            return false;
        }

        return $this->content->created_by === $user->getId();

    }

    public function addTaskResponsible($user, $sendNotification = true)
    {
        $user = (is_string($user)) ? User::findOne(['guid' => $user]) : $user;

        if (!$user) {
            return false;
        }

        if (!$this->isTaskResponsible($user)) {
            $taskResponsible = new TaskUser([
                'task_id' => $this->id,
                'user_id' => $user->id,
                'user_type' => self::USER_RESPONSIBLE,
                'sendNotificationOnCreation' => $sendNotification
            ]);
            return $taskResponsible->save();
        }

        return false;
    }

    public function isOverdue()
    {
        return $this->schedule->isOverdue();
    }


    /**
     * Returns an ActiveQuery for all assigned task users of this task.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskReminder()
    {
        $query = $this->hasMany(TaskReminder::className(), ['task_id' => 'id']);
        return $query;
    }


    // ###########  handle task items  ###########

    /**
     * Returns an ActiveQuery for all task items of this task.
     *
     * @return ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(TaskItem::class, ['task_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function hasItems()
    {
        // Todo check task_items and subtask-Items
        return !empty($this->items);
    }

    public function saveNewItems()
    {
        if ($this->newItems == null) {
            return;
        }

        foreach ($this->newItems as $itemText) {
            $this->addItem($itemText);
        }

        // Reset cached items
        unset($this->items);
    }

    public function addItem($itemText)
    {
        if (trim($itemText) === '') {
            return;
        }

        $item = new TaskItem();
        $item->task_id = $this->id;
        $item->title = $itemText;
        $item->save();
        return $item;
    }


    /**
     * Sets the newItems array, which is used for creating and updating (afterSave)
     * the task, by saving all valid item title contained in the given array.
     * @param array $newItemArr
     */
    public function setNewItems($newItemArr)
    {
        $this->newItems = TaskItem::filterValidItems($newItemArr);
    }

    /**
     * Sets the editItems array, which is used for updating (afterSave)
     * the task. The given array has to contain task item ids as key and an title
     * as values.
     * @param array $editItemArr
     */
    public function setEditItems($editItemArr)
    {
        $this->editItems = TaskItem::filterValidItems($editItemArr);
    }

    /**
     * @param array $items
     * @throws \yii\db\Exception
     */
    public function confirm($items = [])
    {
        foreach ($items as $itemID) {
            $item = TaskItem::findOne(['id' => $itemID, 'task_id' => $this->id]);
            if ($item) {
                $item->completed = 1;
                $item->save();
            }
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function completeItems()
    {
        TaskItem::updateAll(['completed' => 1], ['task_id' => $this->id]);
    }

    /**
     * Returns the total number of confirmed users got this message
     *
     * @return int
     */
    public function getConfirmedCount()
    {
        return $this->getItems()->where(['completed' => true])->count();
    }

    public function isPending()
    {
        return $this->state->isPending();
    }

    public function isInProgress()
    {
        return $this->state->isInProgress();
    }

    public function isPendingReview()
    {
        return $this->state->isPendingReview();
    }

    public function isCompleted()
    {
        return $this->state->isCompleted();
    }

    public function canReview($user = null)
    {
        return $this->review && $this->isTaskResponsible($user);
    }

    /**
     * Additional canEdit check for responsible users.
     * @return bool
     * @see Content::canEdit()
     */
    public function canEdit()
    {
        if ($this->isNewRecord) {
            return $this->content->container->can([CreateTask::class, ManageTasks::class]);
        } else if (!$this->hasTaskResponsible()) {
            return $this->content->container->can([ManageTasks::class]);
        }

        return $this->isTaskResponsible();
    }

    // ###########  handle notifications  ###########

    /**
     * Filters responsible users from the list of assigned users
     *
     * @return array|User[]
     */
    public function filterResponsibleAssigned()
    {
        $responsible = $this->getTaskResponsibleUsers()->select(['id']);

        $filteredAssigned = $this->getTaskAssignedUsers()
            ->where(['not in', 'id', $responsible])
            ->all();
        return $filteredAssigned;
    }

    public function deleteOldNotifications($notificationClassName = null)
    {
        if (!$notificationClassName) {
            // delete all old notifications - used for reset
            $notifications = Notification::find()->where(['source_class' => self::class, 'source_pk' => $this->id, 'space_id' => $this->content->container->id])->all();
            foreach ($notifications as $notification) {
                $notification->delete();
            }
        } else {
            // delete specific old notifications
            $notifications = Notification::find()->where(['class' => $notificationClassName, 'source_class' => self::class, 'source_pk' => $this->id, 'space_id' => $this->content->container->id])->all();
            foreach ($notifications as $notification) {
                $notification->delete();
            }
        }
    }

    /**
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns a badge for the snippet
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getBadge()
    {
        if ($this->schedule->isOverdue())
            return Label::danger(Yii::t('TasksModule.views_index_index', 'Overdue'))->icon('fa fa-exclamation-triangle')->options(['style' => 'margin-right: 3px;'])->right();

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        $itemTitles = "";
        $itemDescriptions = "";

        foreach ($this->items as $item) {
            $itemTitles .= $item->title;
            $itemDescriptions .= $item->description;
        }

        return [
            'title' => $this->title,
            'description' => $this->description,
            'itemTitles' => $itemTitles,
            'itemDescriptions' => $itemDescriptions
        ];
    }

    /**
     * handle task specific permissions
     * @return bool
     */
    public function canProcess($user = null)
    {
        if (!$user && Yii::$app->user->isGuest) {
            return false;
        }

        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }

        $permissionManager = new ContentContainerPermissionManager([
            'contentContainer' => $this->content->container,
            'subject' => $user
        ]);

        return (!$this->hasTaskAssigned() && $permissionManager->can(ProcessUnassignedTasks::class));
    }

    /**
     * handle task specific permissions
     * @return bool
     */
    public function canCheckItems()
    {
        return (($this->isTaskResponsible() || $this->isTaskAssigned() || $this->canProcess()) && (!($this->isCompleted())));
    }

    /**
     * Only responsible users can review task
     * @return bool
     */
    public function canReviewTask()
    {
        return (self::isTaskResponsible());
    }

    /**
     * handle task specific permissions
     * @return bool
     */
    public function canResetTask()
    {
        return (self::isTaskResponsible() && (self::isCompleted()));
    }

    /**
     * handle task specific permissions
     * @return bool
     */
    public function canResortItems()
    {
        return (self::isTaskResponsible() && (!self::isCompleted()));
    }

    // ###########  handle view-specific  ###########

    /**
     * Returns the percentage of task
     *
     * @return int
     */
    public function getPercent()
    {
        $denominator = $this->getItems()->count();
        // add STATUS_IN_PROGRESS and STATUS_COMPLETED
        $denominator += 2;
        // handle special status STATUS_PENDING_REVIEW
        if ($this->review) {
            $denominator += 1;
        }
        if ($denominator == 0)
            return 0;


        $counter = $this->getConfirmedCount();
        if (self::isInProgress())
            $counter += 1;
        elseif (self::isCompleted() && !$this->review)
            $counter += 2;
        elseif (self::isPendingReview() && $this->review)
            $counter += 2;
        elseif (self::isCompleted() && $this->review)
            $counter += 3;

        return $counter / $denominator * 100;
    }

    /**
     * Returns additional labels
     *
     * @param array $labels
     * @param bool $includeContentName
     * @return Label[]|string[]
     */
    public function getLabels($labels = [], $includeContentName = true)
    {
        switch ($this->status) {
            case self::STATUS_PENDING :
                $labels[] = Label::defaultType(Yii::t('TasksModule.views_index_index', 'Pending'))->icon('fa fa-info-circle')->sortOrder(350);
                break;
            case self::STATUS_IN_PROGRESS :
                $labels[] = Label::info(Yii::t('TasksModule.views_index_index', 'In Progress'))->icon('fa fa-edit')->sortOrder(350);
                break;
            case self::STATUS_PENDING_REVIEW :
                $labels[] = Label::warning(Yii::t('TasksModule.views_index_index', 'Pending Review'))->icon('fa fa-exclamation-triangle')->sortOrder(350);
                break;
            case self::STATUS_COMPLETED :
                $labels[] = Label::success(Yii::t('TasksModule.views_index_index', 'Completed'))->icon('fa fa-check-square')->sortOrder(350);
                break;
            default:
                break;
        }

        if ($this->schedule->isOverdue())
            $labels[] = Label::danger(Yii::t('TasksModule.views_index_index', 'Overdue'))->icon('fa fa-exclamation-triangle')->sortOrder(360);

        return parent::getLabels($labels, $includeContentName);
    }

    public function moveItemIndex($itemId, $newIndex)
    {
        $this->checklist->moveItemIndex($itemId, $newIndex);
    }

    /**
     * @throws \Exception
     * @throws \yii\db\StaleObjectException
     * @throws \Throwable
     */
    public function beforeRemoveUser()
    {
        $notifications = Notification::find()->where(['source_class' => self::className(), 'source_pk' => $this->id, 'space_id' => $this->content->contentContainer->id])->all();
        foreach ($notifications as $notification) {
            $notification->delete();
        }
    }

    /**
     * @param $userId
     * @return bool
     * @throws \Exception
     * @throws \yii\db\StaleObjectException
     * @throws \Throwable
     */
    public function removeUser($userId)
    {
        if (empty($userId) || !isset($userId)) {
            return false;
        }

        $taskAssigned = $this->getAssignedTaskUsers()->where(['task_user.user_id' => $userId])->all();
        foreach ($taskAssigned as $assigned) {
            $assigned->delete();
        }
        $taskResponsible = $this->getTaskResponsible()->where(['task_user.user_id' => $userId])->all();
        foreach ($taskResponsible as $responsible) {
            $responsible->delete();
        }
    }

    public function getColor()
    {
        if ($this->task_list_id && $this->list) {
            return $this->list->getColor();
        }
    }

    // ###########  handle task related accounts  ###########

    public function manageSpaceAccount()
    {
        $spaceAccountTitle = "Task#$this->id ( $this->title )";
        $spaceAccountUserId = empty($this->responsibleUsers) ? null : User::findOne(['guid' => $this->responsibleUsers[0]])->id;

        if (null === ($taskSpaceAccount = TaskAccount::findOne(['task_id' => $this->id]))) {

            $spaceAccount = new Account([
                'title' => $spaceAccountTitle,
                'space_id' => $this->content->container->id,
                'account_type' => Account::TYPE_TASK,
                'user_id' => $spaceAccountUserId
            ]);

            $spaceAccount->save();

            $taskSpaceAccount = new TaskAccount([
                'task_id' => $this->id,
                'account_id' => $spaceAccount->id,
                'account_type' => self::SPACE_ACCOUNT,
            ]);

            return $taskSpaceAccount->save();
        } else {
            $spaceAccount = Account::findOne(['id' => $taskSpaceAccount->account_id]);

            $spaceAccount->title = $spaceAccountTitle;
            $spaceAccount->user_id = $spaceAccountUserId;

            return $spaceAccount->save();
        }
    }

    public function getTaskAccounts()
    {
        return $this->hasMany(TaskAccount::class, ['task_id' => 'id']);
    }

    public function getAccount($accountType)
    {
        return $this
            ->hasOne(Account::class, ['id' => 'account_id'])
            ->via('taskAccount', function ($query) use ($accountType) {
                $query->andWhere(['account_type' => $accountType]);
            })
            ->one();
    }

    public function hasAccount($accountType)
    {
        return is_null($this->getAccount($accountType)) ? false : true;
    }
}
