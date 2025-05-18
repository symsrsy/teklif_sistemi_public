# Tedarikçi Teklif Otomasyon Sistemi

Bu proje, firmaların tedarikçilerle olan teklif süreçlerini otomatikleştirmek amacıyla geliştirilmiş Laravel tabanlı bir sistemdir.

## Özellikler

- Teklif talebi oluşturma
- Tüm tedarikçilere otomatik e-posta gönderme
- Tedarikçilerden gelen e-posta cevaplarını IMAP üzerinden okuma
- Gelen verileri otomatik parse ederek veritabanına kaydetme
- Admin panelinden teklifleri karşılaştırma ve seçme
- Seçilen teklifi PDF formatında raporlama
- Tedarikçilere özel giriş paneli

## Kullanılan Teknolojiler

- Laravel 10
- Blade Template Engine
- Laravel Mailables
- Webklex IMAP Package
- MySQL
- Cron Job Scheduler
- DomPDF

