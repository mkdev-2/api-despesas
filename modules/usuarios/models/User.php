<?php
namespace app\modules\usuarios\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use Yii;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use app\modules\financeiro\models\Despesa;

class User extends ActiveRecord implements IdentityInterface
{
    public $password; // Atributo para lidar com a senha em texto puro

    public static function tableName()
    {
        return 'users';
    }

    public function rules()
    {
        return [
            // Regras para ambos os cenários (create e update)
            [['username', 'email'], 'required', 'message' => '{attribute} é um campo obrigatório.'],
            [['email'], 'email', 'message' => 'O endereço de email não é válido.', 'skipOnEmpty' => false, 'enableIDN' => true, 'allowName' => true],
            [['email', 'username'], 'unique', 'message' => 'Este {attribute} já está em uso.'],
            [['username'], 'string', 'max' => 20, 'tooLong' => 'O nome de usuário não pode exceder {max} caracteres.'],
            [['email'], 'string', 'max' => 255, 'tooLong' => 'O email não pode exceder {max} caracteres.'],
            
            // Regras específicas para o cenário de criação
            ['password', 'required', 'on' => 'create', 'message' => 'A senha é obrigatória.'],
            [['password'], 'string', 'min' => 6, 'on' => ['create', 'update'], 'tooShort' => 'A senha deve conter pelo menos {min} caracteres.'],
        ];
    }

    /**
     * Define cenários disponíveis para validação
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['username', 'email', 'password']; // Cenário para criação de usuário
        $scenarios['update'] = ['username', 'email', 'password']; // Cenário para atualização de usuário
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        
        // Remove campos sensíveis
        unset($fields['password_hash'], $fields['auth_key'], $fields['password']);
        
        return $fields;
    }

    /**
     * Método a ser executado antes de salvar
     * @param bool $insert Se é uma inserção ou atualização
     * @return bool Se deve continuar a operação de salvamento
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Lógica específica para User
            return true;
        }
        return false;
    }

    /**
     * Encontra um usuário pelo email
     * @param string $email Email do usuário
     * @return User|null Instância do usuário ou null
     */
    public static function encontrarPorEmail($email)
    {
        return static::findOne(['email' => $email, 'deleted_at' => null]);
    }

    /**
     * Encontra um usuário pelo nome de usuário
     * @param string $username Nome de usuário
     * @return User|null Instância do usuário ou null
     */
    public static function encontrarPorUsername($username)
    {
        return static::findOne(['username' => $username, 'deleted_at' => null]);
    }

    /**
     * Encontra a identidade do usuário pelo ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'deleted_at' => null]);
    }

    /**
     * Encontra a identidade do usuário pelo token de acesso.
     * Utiliza a biblioteca JWT para validar o token em vez de buscar no banco de dados.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        try {
            // Parse do token
            $token = (new Parser())->parse((string) $token);
            
            // Verificar assinatura
            $signer = new Sha256();
            if (!$token->verify($signer, Yii::$app->jwt->key)) {
                return null;
            }
            
            // Verificar se o token não expirou
            $data = new ValidationData();
            $data->setCurrentTime(time());
            if (!$token->validate($data)) {
                return null;
            }
            
            // Obter o ID do usuário do token e buscar no banco
            $uid = $token->getClaim('uid');
            return static::findOne(['id' => $uid, 'deleted_at' => null]);
        } catch (\Exception $e) {
            Yii::error('Erro ao validar o token JWT: ' . $e->getMessage(), 'jwt');
            return null;
        }
    }

    /**
     * Encontra um usuário pelo nome de usuário.
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'deleted_at' => null]);
    }

    /**
     * Encontra um usuário pelo email.
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'deleted_at' => null]);
    }

    /**
     * Retorna o ID do usuário.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retorna a chave de autenticação.
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * Valida a chave de autenticação.
     */
    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    /**
     * Valida a senha do usuário.
     * @param string $senha Senha a ser validada
     * @return bool Se a senha é válida
     */
    public function validarSenha($senha)
    {
        return Yii::$app->security->validatePassword($senha, $this->password_hash);
    }

    /**
     * Compatibilidade com método original para validação de senha
     * @param string $password Senha a ser validada
     * @return bool Se a senha é válida
     */
    public function validatePassword($password)
    {
        return $this->validarSenha($password);
    }

    /**
     * Define a senha do usuário.
     * @param string $senha Nova senha a ser definida
     */
    public function definirSenha($senha)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($senha);
    }

    /**
     * Compatibilidade com método original para definição de senha
     * @param string $password Nova senha a ser definida
     */
    public function setPassword($password)
    {
        $this->definirSenha($password);
    }

    /**
     * Gera uma chave de autenticação para o usuário.
     */
    public function gerarChaveAutenticacao()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Compatibilidade com método original para geração de auth key
     */
    public function generateAuthKey()
    {
        $this->gerarChaveAutenticacao();
    }

    /**
     * Método estático para registrar um novo usuário.
     * @param string $username Nome de usuário
     * @param string $email Email do usuário
     * @param string $password Senha do usuário
     * @return User|null Novo usuário ou null em caso de erro
     */
    public static function registrar($username, $email, $password)
    {
        $user = new self();
        $user->username = $username;
        $user->email = $email;
        $user->setPassword($password);
        $user->generateAuthKey();
        return $user->save() ? $user : null;
    }

    /**
     * Compatibilidade com método original para registro de usuário
     */
    public static function register($username, $email, $password)
    {
        return self::registrar($username, $email, $password);
    }

    /**
     * Retorna a relação com a tabela de despesas
     */
    public function getDespesas()
    {
        return $this->hasMany(Despesa::class, ['user_id' => 'id']);
    }

    /**
     * @return array Labels para os atributos
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Usuário',
            'email' => 'Email',
            'password' => 'Senha',
            'password_hash' => 'Hash da Senha',
            'auth_key' => 'Chave de Autenticação',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
            'deleted_at' => 'Excluído em',
        ];
    }
} 