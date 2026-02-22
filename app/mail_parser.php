<?php
// app/mail_parser.php

class MailParser {
    private $stream;
    private $msgNum;
    
    public $subject;
    public $fromEmail;
    public $fromName;
    public $messageId;
    public $receivedAt;
    public $headersRaw;
    public $sizeBytes;
    
    public $textBody = '';
    public $htmlBody = '';
    
    // Arrays for CC and To
    public $toRaw = [];
    public $ccRaw = [];
    
    public $attachments = []; // {filename, content, type, encoding}

    public function __construct($stream, $msgNum) {
        $this->stream = $stream;
        $this->msgNum = $msgNum;
    }

    public function parse() {
        $headerInfo = imap_headerinfo($this->stream, $this->msgNum);
        $this->headersRaw = imap_fetchheader($this->stream, $this->msgNum);
        
        $this->subject = isset($headerInfo->subject) ? $this->decodeMimeStr($headerInfo->subject) : '';
        $this->messageId = $headerInfo->message_id ?? null;
        $this->receivedAt = isset($headerInfo->udate) ? date('Y-m-d H:i:s', $headerInfo->udate) : date('Y-m-d H:i:s');
        $this->sizeBytes = $headerInfo->Size ?? 0;

        // Process From
        if (isset($headerInfo->from[0])) {
            $this->fromEmail = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;
            $this->fromName = isset($headerInfo->from[0]->personal) ? $this->decodeMimeStr($headerInfo->from[0]->personal) : '';
        }

        // Process To
        if (isset($headerInfo->to)) {
            foreach ($headerInfo->to as $tc) {
                if (isset($tc->mailbox) && isset($tc->host)) {
                    $this->toRaw[] = $tc->mailbox . '@' . $tc->host;
                }
            }
        }

        // Process CC
        if (isset($headerInfo->cc)) {
            foreach ($headerInfo->cc as $cc) {
                if (isset($cc->mailbox) && isset($cc->host)) {
                    $this->ccRaw[] = $cc->mailbox . '@' . $cc->host;
                }
            }
        }

        // Find extended recipients in raw headers for better mailbox mapping
        $this->extractExtendedRecipients();

        $structure = imap_fetchstructure($this->stream, $this->msgNum);
        if ($structure) {
            $this->parseStructure($structure, '');
        }
    }

    private function extractExtendedRecipients() {
        // Headers like Delivered-To, X-Original-To, Envelope-To
        $headersLines = explode("\n", $this->headersRaw);
        foreach ($headersLines as $line) {
            if (preg_match('/^(Delivered-To|X-Original-To|Envelope-To):\s*(.+)$/i', $line, $matches)) {
                $email = filter_var(trim($matches[2], " <>"), FILTER_SANITIZE_EMAIL);
                if ($email && !in_array($email, $this->toRaw)) {
                    array_unshift($this->toRaw, $email); // Prioritize these
                }
            }
        }
    }

    private function parseStructure($structure, $partNum) {
        if (isset($structure->parts) && count($structure->parts)) {
            foreach ($structure->parts as $index => $subStructure) {
                $section = $partNum ? $partNum . '.' . ($index + 1) : (string)($index + 1);
                $this->parseStructure($subStructure, $section);
            }
        } else {
            // It's a single part
            $section = $partNum ?: '1';
            $content = imap_fetchbody($this->stream, $this->msgNum, $section);
            
            if ($structure->encoding == 3) {
                $content = base64_decode($content);
            } elseif ($structure->encoding == 4) {
                $content = quoted_printable_decode($content);
            }

            $params = [];
            if ($structure->parameters) {
                foreach ($structure->parameters as $p) {
                    $params[strtolower($p->attribute)] = $p->value;
                }
            }
            if ($structure->dparameters) {
                foreach ($structure->dparameters as $p) {
                    $params[strtolower($p->attribute)] = $p->value;
                }
            }

            $isAttachment = false;
            $filename = '';

            if (isset($params['filename']) || isset($params['name'])) {
                $isAttachment = true;
                $filename = $params['filename'] ?? $params['name'];
            } elseif ($structure->ifdisposition && strtolower($structure->disposition) == 'attachment') {
                $isAttachment = true;
                $filename = 'unknown_file_' . time();
            }

            if ($isAttachment) {
                $this->attachments[] = [
                    'filename' => $this->decodeMimeStr($filename),
                    'content' => $content,
                    'type' => $this->getMimeType($structure),
                    'size' => $structure->bytes ?? strlen($content)
                ];
            } else {
                if ($structure->type == 0) { // TEXT
                    if (strtolower($structure->subtype) == 'plain') {
                        $this->textBody .= $content;
                    } elseif (strtolower($structure->subtype) == 'html') {
                        $this->htmlBody .= $content;
                    }
                }
            }
        }
    }

    private function getMimeType($structure) {
        $primaryTypes = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
        $type = 'APPLICATION';
        if (isset($primaryTypes[$structure->type])) {
            $type = $primaryTypes[$structure->type];
        }
        return $type . '/' . $structure->subtype;
    }

    private function decodeMimeStr($string) {
        $elements = imap_mime_header_decode($string);
        $decoded = '';
        foreach ($elements as $element) {
            if ($element->charset != 'default' && strtolower($element->charset) != 'utf-8') {
                $decoded .= @mb_convert_encoding($element->text, 'UTF-8', $element->charset);
            } else {
                $decoded .= $element->text;
            }
        }
        return $decoded;
    }

    public function getTargetMailbox() {
        foreach ($this->toRaw as $email) {
            $email = strtolower(trim($email, " <>"));
            if (str_ends_with($email, '@evicio.site')) {
                return $email;
            }
        }
        // Fallback
        return strtolower(trim($this->toRaw[0] ?? 'unknown@evicio.site', " <>"));
    }
}
