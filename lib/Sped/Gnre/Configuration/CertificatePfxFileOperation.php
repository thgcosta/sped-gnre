<?php

/**
 * Este arquivo é parte do programa GNRE PHP
 * GNRE PHP é um software livre; você pode redistribuí-lo e/ou
 * modificá-lo dentro dos termos da Licença Pública Geral GNU como
 * publicada pela Fundação do Software Livre (FSF); na versão 2 da
 * Licença, ou (na sua opinião) qualquer versão.
 * Este programa é distribuído na esperança de que possa ser  útil,
 * mas SEM NENHUMA GARANTIA; sem uma garantia implícita de ADEQUAÇÃO a qualquer
 * MERCADO ou APLICAÇÃO EM PARTICULAR. Veja a
 * Licença Pública Geral GNU para maiores detalhes.
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU
 * junto com este programa, se não, escreva para a Fundação do Software
 * Livre(FSF) Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Sped\Gnre\Configuration;

use Sped\Gnre\Configuration\FileOperation;
use Sped\Gnre\Exception\CannotOpenCertificate;
use Sped\Gnre\Exception\UnableToWriteFile;

/**
 * Classe responsável por escrever novos arquivos com os dados extraidos do certificado e manipular
 * os metadados utilizados para a conexão com a sefaz
 * @package     gnre
 * @subpackage  configuration
 * @author      Matheus Marabesi <matheus.marabesi@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-howto.html GPL
 * @version     1.0.0
 */
class CertificatePfxFileOperation extends FileOperation
{

    /**
     * O nome da pasta em que os meta dados dos certificados são armazenados.
     * Essa pasta ficará abaixo da pasta /certs ficando então /certs/metadata
     * @var string
     */
    private $metadataFolder = 'metadata';

    /**
     * Caminho e o nome do arquivo completo do certificado a ser utilizado
     * @var string
     */
    private $pathToWrite;

    /**
     * {@inheritdoc}
     */
    public function __construct($filePath)
    {
        parent::__construct($filePath);

        $explodePath = explode('/', $this->filePath);
        $total = count($explodePath);

        $this->fileName = str_replace('.pfx', '.pem', $explodePath[$total - 1]);

        $explodePath[$total - 1] = $this->metadataFolder;

        array_push($explodePath, $this->fileName);

        $this->pathToWrite = implode('/', $explodePath);
    }

    /**
     * Abre um certificado enviado com a senha informada
     * @param  string $password A senha necessária para abrir o certificado
     * @return array  Com os dados extraidos do certificado
     * @throws CannotOpenCertificate Caso a senha do certificado for inválida
     * @since  1.0.0
     */
    public function open($password)
    {
        $key = file_get_contents($this->filePath);
        $dataCertificate = array();
        if (!openssl_pkcs12_read($key, $dataCertificate, $password)) {
            throw new CannotOpenCertificate($this->filePath);
        }

        return $dataCertificate;
    }

    /**
     * Método utilizado para inserir um determinado conteúdo em um arquivo com os dados
     * extraídos do certificado
     * @param  string  $content  Conteúdo desejado a ser escrito no arquivo
     * @param \Sped\Gnre\Configuration\FilePrefix $filePrefix
     * @throws UnableToWriteFile Caso não seja possível escrever no arquivo
     * @return string Retorna o caminho completo do arquivo em que foi escrito o conteúdo enviado
     * @since  1.0.0
     */
    public function writeFile($content, FilePrefix $filePrefix)
    {
        $pathToWrite = $filePrefix->apply($this->pathToWrite);
        
        // Extrai o diretório do caminho do arquivo
        $directory = dirname($pathToWrite);
        
        // Verifica se o diretório existe
        if (!is_dir($directory)) {
            // Cria o diretório, incluindo diretórios pai, se necessário
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
        
        // Tenta escrever o conteúdo no arquivo
        if (file_put_contents($pathToWrite, $content) === false) {
            throw new UnableToWriteFile($this->pathToWrite);
        }
        
        return $pathToWrite;
    }

}
