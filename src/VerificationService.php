<?php

namespace App;

use Exception;

class VerificationService {
    
    public static function generateCode() {
        return sprintf("%06d", random_int(100000, 999999));
    }
    
    public static function sendSMSCode($phone, $code) {
        // В реальной среде здесь был бы SMS API (Twilio, SMS.ru, etc.)
        // Для демонстрации логируем код
        try {
            // Логируем код для тестирования
            error_log("SMS Code for {$phone}: {$code}");
            
            // В реальном приложении здесь бы был вызов SMS API
            // Пример: $smsService->send($phone, "Код верификации Хром-KZ: {$code}");
            
            return true;
        } catch (Exception $e) {
            error_log("Error sending SMS code: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendEmailCode($email, $code) {
        try {
            // Логируем код для тестирования
            error_log("Email Code for {$email}: {$code}");
            
            // В реальном приложении здесь бы была отправка email
            // $emailService = new EmailService();
            // $subject = "Код верификации - Хром-KZ Логистика";
            // return $emailService->sendEmail($email, $subject, $message);
            
            return true;
        } catch (Exception $e) {
            error_log("Error sending email code: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendVerificationCode($phone, $email, $code) {
        $smsResult = false;
        $emailResult = false;
        
        // Отправляем SMS если есть номер телефона
        if (!empty($phone)) {
            $smsResult = self::sendSMSCode($phone, $code);
        }
        
        // Отправляем Email если есть адрес
        if (!empty($email)) {
            $emailResult = self::sendEmailCode($email, $code);
        }
        
        // Возвращаем true если хотя бы один способ сработал
        return $smsResult || $emailResult;
    }
    
    public static function formatPhoneNumber($phone) {
        // Очищаем номер от лишних символов, кроме +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Если номер начинается с 87, оставляем как есть
        if (preg_match('/^87\d{9}$/', $cleaned)) {
            return $cleaned;
        }
        
        // Если номер начинается с +77, оставляем как есть
        if (preg_match('/^\+77\d{9}$/', $cleaned)) {
            return $cleaned;
        }
        
        // Если номер без +77, добавляем +77
        $numbersOnly = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numbersOnly) == 10 && substr($numbersOnly, 0, 1) == '7') {
            return '+7' . $numbersOnly;
        }
        
        // Если номер с 8 в начале, заменяем на +77
        if (strlen($numbersOnly) == 11 && substr($numbersOnly, 0, 1) == '8') {
            return '+77' . substr($numbersOnly, 1);
        }
        
        return $cleaned;
    }
    
    public static function validatePhone($phone) {
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Проверяем Kazakhstan форматы: +77xxxxxxxxx или 87xxxxxxxxx
        return preg_match('/^\+77\d{9}$/', $cleaned) || preg_match('/^87\d{9}$/', $cleaned);
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}