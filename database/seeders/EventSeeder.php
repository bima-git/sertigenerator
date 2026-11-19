<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Daftar kode kegiatan resmi dari dokumen "KODE SERTIFIKAT XCODE".
 * Kode boleh dipakai bersama beberapa versi judul (PRD 5.2 aturan 1),
 * jadi kunci unik seeder adalah kombinasi kode + judul + penerbit.
 * Dijalankan ulang aman: data yang sudah ada tidak diubah.
 */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        $dibuat = 0;

        foreach ($this->events() as $data) {
            $event = Event::query()->firstOrCreate(
                ['kode' => $data['kode'], 'judul' => $data['judul'], 'penerbit' => $data['penerbit']],
                $data + ['bernomor' => true, 'pakai_qr' => true, 'program_default' => null, 'aktif' => true]
            );
            $dibuat += (int) $event->wasRecentlyCreated;
        }

        $this->command?->info("EventSeeder selesai: {$dibuat} kegiatan baru dibuat.");
    }

    /** @return array<int, array<string, mixed>> */
    private function events(): array
    {
        $seminar = fn (string $kode, string $judul, string $penerbit = 'Xcodetraining') => compact('kode', 'judul', 'penerbit') + ['jenis' => 'seminar'];
        $bootcamp = fn (string $kode, string $judul, ?string $program) => [
            'kode' => $kode, 'judul' => $judul, 'penerbit' => 'Bootcamp', 'jenis' => 'bootcamp', 'program_default' => $program,
        ];

        return [
            // Bootcamp: contoh asli X-Code Bootcamp #15 bernomor 15/001/004/Bootcamp/XI/2026 dan memakai QR.
            $bootcamp('15', 'Bootcamp Pentest', 'Penetration Testing & Cyber Security Engineer'),
            $bootcamp('16', 'Bootcamp Cloud', null),
            $bootcamp('21', 'Devops Bootcamp', null),

            // Sertifikat training dan kelas
            $seminar('16', 'X-code Training XIT (Elite Kelas Bersama) Ethical Hacking & Security Excellence'),
            $seminar('17', 'X-code Training XPT'),
            $seminar('18', 'Training Advanced'),
            $seminar('118', 'PRIVATE Advanced Web Hacking & Security'),
            $seminar('19', 'Elite PRIVATE'),
            $seminar('19', 'Ethical Elite Hacker v11 Hybrid 2026'),
            $seminar('69', 'Cyber Analysis'),
            $seminar('61', 'Elite Penetration Testing'),
            $seminar('10', 'Cyber Sentinel Secure', 'CSS'),
            $seminar('20', 'X-code Workshop'),
            $seminar('50', 'Go Training Hacking Router & IP Camera (IoT)'),
            $seminar('51', 'Go Training Basic Web Hacking & Security'),
            $seminar('60', 'Kelas Video Mikrotik'),
            $seminar('70', 'Sertifikasi Ujian Linux System Administrator & Security'),
            $seminar('71', 'Training Linux System Administrator & Security'),
            $seminar('81', 'Training Penetration Testing Website'),
            $seminar('82', 'Pelatihan Pembangunan Infrastruktur'),
            $seminar('83', 'Pelatihan Linux Debian untuk Server'),

            // Seminar dan webinar
            $seminar('23', 'Ethical Web Application Hacking & Security'),
            $seminar('23', 'Webinar Siber Expert Asia', 'SiberExpert'),
            $seminar('24', 'Ethical Network Hacking & Security'),
            $seminar('25', 'Ethical Web Hacking, Post Exploitation Hacking & Defense'),
            $seminar('255', 'Ethical Web Hacking, Post Exploitation & Defense (+ GSOCKET)'),
            $seminar('26', 'Membangun Hoster dengan Xcodehoster'),
            $seminar('27', 'Membangun Server dengan Memanfaatkan Reverse Proxy & Docker'),
            $seminar('28', 'Web Server & Security Hardening v4'),
            $seminar('29', 'Ethical Wireless Hacking'),
            $seminar('30', 'Training Exploit Development for Corporate'),
            $seminar('31', 'Basic Ethical Wordpress Hacking & Security'),
            $seminar('32', 'Proxmox Virtual Environment (Proxmox VE) untuk Kebutuhan Server'),
            $seminar('33', 'Web Server, Virtualhost, Cloudflare, SSL & Security Hardening'),
            $seminar('34', 'Ethical Windows Hacking & Post Exploitation'),
            $seminar('35', 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI'),
            $seminar('335', 'Penetration Testing + AI'),
            $seminar('36', 'Reverse Proxy dan Proxmox untuk Menggunakan Layanan VPS'),
            $seminar('37', 'Linux Networking & Security'),
            $seminar('38', 'Ethical Hacking & Exploit Development'),
            $seminar('39', 'Membangun Infrastruktur Server yang Tangguh v4 dengan Failover, Fallback, Mirroring, dan Backup di Ubuntu Server 24.04'),
            $seminar('39', 'Membangun Infrastruktur Server Tangguh V5 di Ubuntu 24.04 dengan High Availability Server Database, Failover Otomatis, Mirroring, Backup, Storage Modern Berbasis Ceph, dan Scalable'),
            // Contoh asli: 39/001/004/SiberExpert/II/2026
            $seminar('39', 'Membangun Infrastruktur Server Tangguh V5 di Ubuntu 24.04 dengan High Availability Server Database, Failover Otomatis, Mirroring, Backup, Storage Modern Berbasis Ceph, dan Scalable', 'SiberExpert'),
            $seminar('40', 'Ethical Hacking dengan Memanfaatkan ZAP v2'),
            $seminar('41', 'Fundamental Blue Team Log Analysis & Detecting Web Attacks'),
            $seminar('42', 'Membangun Layanan Otomatisasi VPS dari Nol dengan LXC untuk Kebutuhan Website'),
            $seminar('43', 'Backup Data secara Otomatis di Proxmox (VM, CT, Files & Database) untuk Keamanan Data'),
            $seminar('44', 'Teknik Pengamanan Maximum Server Ubuntu 24.04'),
            $seminar('45', 'Membangun Layanan Internet Kantor dengan Mikrotik RouterOS v7'),
            $seminar('46', 'Membangun Virtual Data Center (Mikrotik Router & Proxmox) untuk Layanan Otomatisasi VPS untuk Kebutuhan Website'),
            $seminar('47', 'Analysis of Malware Running on the Windows Operating System'),
            $seminar('48', 'Essential Ethical Web CMS Hacking & Security (WordPress & Joomla)'),
            $seminar('49', 'Membangun Infrastruktur Modern Berbasis HTTPS Reverse Proxy dengan Proxmox'),
            $seminar('52', 'From Zero to Multi-Cloud HA Cluster dengan Cloudflare Tunnel dan Zero Trust - Bangun Private Cloud Canggih & Tangguh dengan LXD, Ceph, OVN, HAProxy (Round Robin, Health Check, Failover), Multi Reverse Proxy, dan DNS Failover dengan Cloudflare Tunnel dan Zero Trust'),
            $seminar('522', 'Bangun Private Cloud Canggih & Tangguh dengan LXD, Ceph, OVN, HAProxy (Round Robin, Health Check, Failover) dengan Cloudflare Tunnel v2 (Tanpa perlu IP Publik, cukup punya Domain saja & SSH ke server private lewat Tail Scale dari internet)'),
            $seminar('53', 'Web Pentest for Gray-box Testing'),
            $seminar('54', 'Membangun Web Server di Proxmox dengan Database Terpisah & Integrasi Cloudflare Tunnel'),
            $seminar('55', 'Membangun Private AI Server dengan DeepSeek dan Cloudflare Tunnel di Ubuntu'),
            $seminar('56', 'Membangun Infrastruktur Public Cloud VPS High-Availability dengan MicroCloud, Ceph, OVN, dan Docker Automation'),
            $seminar('57', 'Belajar Mikrotik untuk Kebutuhan Kantor, Server On-Premise, IP Camera, dan Router'),
            $seminar('58', 'Implementasi Wazuh SIEM dan WAF ModSecurity dari Nol - Instalasi, Konfigurasi Agen, Pemblokiran Serangan Web dengan WAF ModSecurity, dan Deteksi Serangan di Ubuntu Server 24.04.3, serta Pemantauan SIEM di Windows'),
            $seminar('588', 'Implementasi Wazuh SIEM, WAF ModSecurity, dan Auditd/Auditctl untuk Monitoring Keamanan dari Nol: Instalasi, Konfigurasi Agen, Pemblokiran Serangan Web dengan ModSecurity, Deteksi Serangan, serta Analisis Post-Exploitation untuk Mengetahui Perintah yang Dieksekusi Attacker secara Detail di Ubuntu Server 24.04, serta Pemantauan SIEM di Windows'),
            $seminar('59', 'Hands-On Membangun Web Shared Hosting di OpenStack'),
            $seminar('59', 'Hands-On Membangun VM untuk Web Server di OpenStack v2'),
            $seminar('62', 'Membangun Infrastruktur dan Server Linux Ubuntu untuk Keamanan yang Tangguh'),
            $seminar('63', 'SQL Injection Uncovered: From Fundamentals to Advanced Techniques in Ethical Hacking'),
            $seminar('64', 'Membangun Layanan Hosting dengan Xcodehoster 2025 v9.0 Pro'),
            $seminar('65', 'Ethical Hacking untuk Aplikasi Web: Teknik Pentesting untuk Mendeteksi Kerentanannya'),
            $seminar('66', 'Membangun Web Server Nginx dengan Keamanan yang Optimal v2'),
            $seminar('67', 'Ethical Hacking dan Eksplorasi Kerentanan dalam OWASP Top 10 2025'),
            $seminar('68', 'Membangun Layanan Server dengan Platform PHP Modern menggunakan FrankenPHP v2'),
            $seminar('72', 'Membangun Server Monolith dengan Docker dengan Cepat dan Mudah'),
            $seminar('72', 'Membangun Server Monolith menggunakan Docker dengan Cepat dan Mudah: Panduan dari Instalasi hingga Deploy v2 (Pengelolaan dengan Portainer)'),
            $seminar('73', 'Membangun Infrastruktur Jaringan dengan Proxmox'),
            $seminar('74', 'Membangun Layanan Otomatisasi VPS menggunakan Docker untuk Kebutuhan Website dengan Cepat dan Mudah'),
            $seminar('74', 'Layanan Otomatisasi VPS menggunakan Docker untuk Kebutuhan Website dengan Cepat dan Mudah v2 (Pengelolaan VPS Docker dengan Portainer.io)'),
            $seminar('75', 'Belajar menjadi Sysadmin v2: Konfigurasi Router Mikrotik dan Linux Ubuntu Server untuk Pemula dalam Pengelolaan Website dan Infrastruktur'),
            $seminar('76', 'Fundamental Belajar FreeBSD untuk Membangun Web Server'),
            $seminar('77', 'Membangun Apache Web Server dengan Keamanan yang Optimal v2'),
            $seminar('78', 'Ethical Hacking pada Router dengan Beragam Teknik'),
            $seminar('79', 'Belajar Mikrotik untuk Pemula v2 - Konfigurasi Dasar untuk Kebutuhan Jaringan Komputer'),
            $seminar('80', 'Membangun Layanan Otomatisasi VPS menggunakan Proxmox (LXC) untuk Kebutuhan Website dan Industri dengan Cepat dan Mudah'),
            $seminar('84', 'Membangun Web Server Nginx dengan Sistem Monitoring menggunakan GoAccess, Grafana, dan Prometheus'),
            $seminar('85', 'Nextcloud dari Nol: Bangun Cloud Sendiri & Bagikan ke Internet di Ubuntu Server 24.04.2'),
            $seminar('86', 'Membangun Layanan Otomatisasi VPS menggunakan KVM dan Proxmox untuk Kebutuhan Website'),
            $seminar('87', 'Nessus & Eksploitasi Kerentanan di Sistem & Aplikasi'),
            $seminar('89', 'Implementasi Wazuh SIEM dari Nol v2: Instalasi, Agen, hingga Deteksi Serangan di Ubuntu Server'),
            $seminar('90', 'Belajar Membangun Sistem Keamanan yang Tangguh dengan Beragam Tools untuk Web Server, termasuk Penerapan WAF ModSecurity pada Web Server Apache2 di Ubuntu Server 24.04.2 LTS'),
            $seminar('91', 'Basic Ethical Web Application Hacking'),
            $seminar('92', 'Bangun Private Cloud yang Canggih dan Tangguh dengan LXD, Ceph, OVN, & HAProxy'),
            $seminar('93', 'Bangun Web Server dengan Keamanan Tangguh dari Nol: WAF ModSecurity dan Monitoring dengan Zabbix di Ubuntu Server 24.04'),
            $seminar('94', 'Workshop Ethical Hacking Web untuk Pemula dengan Kali Linux'),
            $seminar('95', 'From Zero to Multi-Cloud HA Cluster: Bangun Private Cloud Canggih & Tangguh dengan LXD, Ceph, OVN, HAProxy (Round Robin, Health Check, Failover), Multi Reverse Proxy, dan DNS Failover dengan Cloudflare'),
            $seminar('96', 'Proxmox Virtual Environment (Proxmox VE) & Router Mikrotik untuk Kebutuhan Infrastruktur'),
            $seminar('97', 'Bangun Infrastruktur dari Nol dengan Server Keamanan Tangguh: WAF ModSecurity, Security Hardening hingga Monitoring di Ubuntu Server 24.04.3 LTS'),
            $seminar('98', 'Membangun Infrastruktur dari Nol dengan Cloudflare Tunnel pada Server Keamanan Tangguh: WAF ModSecurity, Security Hardening, hingga Monitoring di Ubuntu Server 24.04.3 LTS'),
            $seminar('99', 'Nextcloud dari Nol: Bangun Cloud Sendiri dengan Cloudflare Tunnel & Bagikan ke Internet di Ubuntu Server 24.04'),
            $seminar('100', 'Membangun Server dengan Cloudflare Tunnel & Zero Trust - Web Server Nginx dengan Keamanan yang Optimal disertai dengan Pemantauan melalui Web'),
            $seminar('101', 'Basic Ethical Hacking pada Target Server Linux dengan Exploit CVE-2026 dan Melakukan Container Escape, Pemasangan Backdoor Akun, Backdoor Reverse Shell, dan Crack Password Linux dengan John The Ripper'),
            $seminar('102', 'Dari Nol hingga Production: Ubuntu Server, Security, dan AI Monitoring'),
            $seminar('103', 'Implementasi Wazuh SIEM, WAF ModSecurity, dan AI dari Nol: Instalasi, Konfigurasi Agen, Pemblokiran Serangan Web dengan Waf ModSecurity, Deteksi Serangan di Ubuntu Server 24.04 dengan Integrasi AI dan Telegram, serta Pemantauan SIEM di Windows'),
            $seminar('104', 'Step-by-Step: Implementasi Private Cloud Berbasis OpenStack dan Ubuntu Server'),
            $seminar('105', 'Vibes Coding with XcodePod'),
        ];
    }
}
