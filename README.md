Портирование блога из yii1.1.* в yii2

- Начало
    + Установка Yii2 Advanced template
- Начальное прототипирование
    + Создание и подключение базы данных
    + Генерация каркаса
- Управление записями
    + Доработка модели Post
    + 
- Управление комментариями

## Начало
По стандартным рецептам, коих уже много в сети, устанавливаем Advanced template в папку доступную из сети. 

Напомню основные моменты способа установку для маленьких:

1. Создать папку для проекта (blog) и распокавать актуальную версию архива шаблона (линк для скачки http://www.yiiframework.com/download)
2. Инициализировать приложение из командной строки:
    php init
    на вопрос варианта установки в режиме разработки можно выбрать опцию под номером 0
3. Создать базу данных (blog) и подключить к приложению в файле blog/common/config/main-local.php:
    'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=localhost;dbname=blog',
        'username' => 'root',
        'password' => 'yourpassword',
        'charset' => 'utf8',
        'table_prefix' => 'tbl_',
    ],
4. Запустить миграцию создания таблиц базы днных:
    yii migrate
5. Убедиться что проект готов к дальнейшему расширению:
    http://localhost/blog/frontend/web

6. Создаем таблицы для нашего блока из готовой модели yii 1.1.*(находится где-то здесь yii/demos/blog/protected/data). Перед тем как запустить sql необходимо сделать поправки:
-  убрать префикс tbl_,
-  исключить таблицу tbl_user, так как в шаблоне advanced уже есть из коробки модель User
-  сделать соответственные поправки в создании внешних ключей в таблицах post и comments:

    CREATE TABLE lookup
    (
        id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(128) NOT NULL,
        code INTEGER NOT NULL,
        type VARCHAR(128) NOT NULL,
        position INTEGER NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

    CREATE TABLE post
    (
        id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(128) NOT NULL,
        content TEXT NOT NULL,
        tags TEXT,
        status INTEGER NOT NULL,
        create_time INTEGER,
        update_time INTEGER,
        author_id INTEGER NOT NULL,
        CONSTRAINT FK_post_author FOREIGN KEY (author_id)
            REFERENCES user (id) ON DELETE CASCADE ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

    CREATE TABLE comment
    (
        id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
        content TEXT NOT NULL,
        status INTEGER NOT NULL,
        create_time INTEGER,
        author VARCHAR(128) NOT NULL,
        email VARCHAR(128) NOT NULL,
        url VARCHAR(128),
        post_id INTEGER NOT NULL,
        CONSTRAINT FK_comment_post FOREIGN KEY (post_id)
            REFERENCES post (id) ON DELETE CASCADE ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

    CREATE TABLE tag
    (
        id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(128) NOT NULL,
        frequency INTEGER DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

     

7. Создаем модели наших таблиц и сгенерируем коды операций CRUD. Для этого запускаем генератор gii в backend -e:
    http://localhost/blog/backend/web/index.php?r=gii

    Соответственно заполняем поля:
    Table Name:
    *
    Namespace:
    common\models

    Жмём [Preview] и убираем галочку с создания модели Migration.php она нам не понадобиться, убедившись что не отмечен пункт overwrite модели User.php жмём на далее [Generate]

    В результате у нас получиться в папке common\models следующие модели:
    common/models/Comment.php
    common/models/Lookup.php
    common/models/Post.php
    common/models/Tag.php 

    После того, как были созданы классы модели, мы можем использовать Crud Generator для генерации кода операций CRUD для них. Сделаем это в backend -е, так как CRUD  - это операция для админки. Создадим CRUD моделей Post и Comment. 
    Для Post:
    Model Class:
    common\models\Post
    Search Model Class:
    common\models\PostSearch
    Controller Class:
    backend\controllers\PostController

    Для Comment:
    Model Class
    common\models\Comment
    Search Model Class
    common\models\CommentSearch
    Controller Class
    backend\controllers\CommentController

    Если заглянете в папку backend/views то увидите, что сгенерированны представления для post и comment контроллеров в одноименные папки.

8. Вы, возможно, заметили, что каркас приложения advanced уже реализует аутентификацию, происходящюю посредством таблицы User БД. 
9. Доработка модели Post 
    Изменение метода rules()

    Изменение метода relations() 

    Добавляем свойство url 

    Текстовое представление для статуса 
10. Создание и редактирование записей 

    Настройка правил доступа

    'access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],

    Правки в действиях create и update 

    Для начала изменим файл _form.php таким образом, чтобы форма собирала только нужные нам данные: title, content, tags и status. Для первых трёх атрибутов мы используем текстовые поля. Для status — выпадающий список с всеми возможными состояниями записи: 
     <?= $form->field($model, 'status')->dropDownList(Lookup::items('PostStatus'),['prompt'=>'Select...']) ?>
     В приведённом коде для получения списка статусов используется вызов Lookup::items('PostStatus'). 

     Далее изменим класс Post таким образом, чтобы он автоматически выставлял некоторые атрибуты (такие, как create_time и author_id) непосредственно перед сохранением записи в БД.

     'timestamp' => [
                        'class' => TimestampBehavior::className(),
                        'attributes' => [
                            ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
                            ActiveRecord::EVENT_BEFORE_UPDATE => ['update_time'],
                        ],
                    ],
                    [
                        'class' => BlameableBehavior::className(),
                        'createdByAttribute' => 'author_id',
                        'updatedByAttribute' => 'author_id',
                    ],

    При сохранении записи мы хотим также обновить информацию о частоте использования тегов в таблице tag. Мы можем реализовать это в методе afterSave(), который автоматически вызывается после успешного сохранения записи в БД. 

11. Отображение записей 
    Изменение действия index 
    Изменение действия view

12. Управление записями
13. Управление комментариями
    
    Доработка модели Comment 
    
    Изменение метода rules() 

    Изменение метода attributeLabels() 

    Создание и отображение комментариев 

    Создание комментариев

    Управление комментариями из админки
    
14. Создание портлета последних комментариев 
    












