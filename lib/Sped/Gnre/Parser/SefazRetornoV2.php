<?php

namespace Sped\Gnre\Parser;

use SimpleXMLElement;

class SefazRetornoV2 {

  private $xml;

  public function __construct($retorno)
  {
    $xml = str_ireplace(["soapenv:", "soap:", "ns1:"], "", $retorno);
    $response = simplexml_load_string($xml);

    $this->xml = $response;
  }

  public function toArray(): array
  {
    $xml = $this->xml;
    $parser = function (SimpleXMLElement $xml, array $collection = []) use ( &$parser ) {
      $nodes = $xml->children();
      $attributes = $xml->attributes();

      if (0 !== count($attributes)) {
        foreach ($attributes as $attrName => $attrValue) {
          $collection["attributes"][$attrName] = strval($attrValue);
        }
      }

      if (0 === $nodes->count()) {
        $collection["value"] = strval($xml);
        return $collection;
      }

      foreach ($nodes as $nodeName => $nodeValue) {
        if (count($nodeValue->xpath("../" . $nodeName)) < 2) {
          if (array_key_exists($nodeName, $collection)) {
            if (!array_key_exists(0, $collection[$nodeName])) {
              $tmp = $collection[$nodeName];
              $collection[$nodeName] = [];
              $collection[$nodeName][] = $tmp;
            }
            $collection[$nodeName][] = $parser($nodeValue);
            continue;
          }
          $collection[$nodeName] = $parser($nodeValue);
          continue;
        }

        $collection[$nodeName][] = $parser($nodeValue);
      }

      return $collection;
    };

    return [
      $xml->getName() => $parser($xml),
    ];
  }

  public function toJson(): string
  {
    return json_encode($this->toArray());
  }

  public function toStdClass(): \stdClass
  {
    return json_decode($this->toJson());
  }

  public function getCodigoRetorno()
  {
    $response = 9999; // 9999 Retorno não mapeado
    $stdClass = $this->toStdClass();
    if(isset($stdClass->Envelope->Body->processarResponse->TRetLote_GNRE->situacaoRecepcao->codigo->value)){
      $response = $stdClass->Envelope->Body->processarResponse->TRetLote_GNRE->situacaoRecepcao->codigo->value;
    }elseif(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->situacaoProcess->codigo->value)){
      $response = $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->situacaoProcess->codigo->value;
    }elseif(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->numeroRecibo->value)){
      $response = 401; // 401 Em processamento;
    }
    return $response;
  }

  public function getMensagemRetorno()
  {
    $stdClass = $this->toStdClass();
    $response = 'Retorno não mapeado';
    if(isset($stdClass->Envelope->Body->processarResponse->TRetLote_GNRE->situacaoRecepcao->descricao->value)){
      $response = $stdClass->Envelope->Body->processarResponse->TRetLote_GNRE->situacaoRecepcao->descricao->value;
    }elseif(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->situacaoProcess->descricao->value)){
      $response = $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->situacaoProcess->descricao->value;
    }elseif(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->numeroRecibo->value)){
      $response = 'Pedido em processamento';
    }
    return $response;
  }

  public function getReciboNumero()
  {
    $stdClass = $this->toStdClass();
    if(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->numeroRecibo->value)){
      return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->numeroRecibo->value;
    }else if(isset($stdClass->Envelope->Body->processarResponse->TRetLote_GNRE->recibo->numero->value)){
      return $stdClass->Envelope->Body->processarResponse->TRetLote_GNRE->recibo->numero->value;
    }
  }

  public function getDataRecibo()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->processarResponse->TRetLote_GNRE->recibo->dataHoraRecibo->value;
  }

  public function getValorPrincipal()
  {
    $stdClass = $this->toStdClass();
    if(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->itensGNRE->item->valor->value)){
      return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->itensGNRE->item->valor->value;
    }
    foreach(
      $stdClass
      ->Envelope
      ->Body
      ->gnreRespostaMsg
      ->TResultLote_GNRE
      ->resultado
      ->guia
      ->itensGNRE
      ->item
      ->valor
      as $valor
    ){
      if($valor->attributes->tipo == '11'){
        return $valor->value;
      }
    }
  }

  public function getAtualizacaoMonetaria()
  {
    $stdClass = $this->toStdClass();
    foreach(
      $stdClass
      ->Envelope
      ->Body
      ->gnreRespostaMsg
      ->TResultLote_GNRE
      ->resultado
      ->guia
      ->itensGNRE
      ->item
      ->valor
      as $valor
    ){
      if(isset($valor->attributes->tipo) && $valor->attributes->tipo == '21'){
        return $valor->value;
      }else{
        return '0.00';
      }
    }
  }

  public function getJuros()
  {
    $stdClass = $this->toStdClass();
    foreach(
      $stdClass
      ->Envelope
      ->Body
      ->gnreRespostaMsg
      ->TResultLote_GNRE
      ->resultado
      ->guia
      ->itensGNRE
      ->item
      ->valor
      as $valor
    ){
      if(isset($valor->attributes->tipo) && $valor->attributes->tipo == '31'){
        return $valor->value;
      }else{
        return '0.00';
      }
    }
  }

  public function getMulta()
  {
    $stdClass = $this->toStdClass();
    foreach(
      $stdClass
      ->Envelope
      ->Body
      ->gnreRespostaMsg
      ->TResultLote_GNRE
      ->resultado
      ->guia
      ->itensGNRE
      ->item
      ->valor
      as $valor
    ){
      if(isset($valor->attributes->tipo) && $valor->attributes->tipo == '41'){
        return $valor->value;
      }else{
        return '0.00';
      }
    }
  }

  public function getOutrosValores()
  {
    $stdClass = $this->toStdClass();
    foreach(
      $stdClass
      ->Envelope
      ->Body
      ->gnreRespostaMsg
      ->TResultLote_GNRE
      ->resultado
      ->guia
      ->itensGNRE
      ->item
      ->valor
      as $valor
    ){
      if($valor->attributes->tipo == '51'){
        return $valor->value;
      }
    }
  }

  public function getValorTotal()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->valorGNRE->value;
  }

  public function getDataPagamento()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->dataPagamento->value;

  }

  public function getDataVencimento()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->dataLimitePagamento->value;
  }

  public function getNossoNumero()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->nossoNumero->value;
  }

  public function getLinhaDigitavel()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->linhaDigitavel->value;
  }

  public function getCodigoBarras()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->codigoBarras->value;
  }

  public function getPDFBase64()
  {
    $stdClass = $this->toStdClass();
    return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->pdfGuias->value;
  }

  public function getCodigoRejeicao()
  {
    $stdClass = $this->toStdClass();
    if(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->motivosRejeicao->motivo->codigo->value)){
      return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->motivosRejeicao->motivo->codigo->value;
    }
    if(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->motivosRejeicao->motivo->codigo->value)){
      return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->motivosRejeicao->motivo->codigo->value;
    }
    return '9999';
  }

  public function getMotivoRejeicao()
  {
    $stdClass = $this->toStdClass();
    if(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->motivosRejeicao->motivo->descricao->value)){
      return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->motivosRejeicao->motivo->descricao->value;
    }
    if(isset($stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->motivosRejeicao->motivo->descricao->value)){
      return $stdClass->Envelope->Body->gnreRespostaMsg->TResultLote_GNRE->resultado->guia->motivosRejeicao->motivo->descricao->value;
    }
    return 'Retorno não mapeado';
  }
}