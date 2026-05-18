Berikut adalah dokumen **`SourceOfTruth.md`** yang dirancang sebagai otoritas tunggal (single source of truth) untuk seluruh pengembangan LifePlanner SIM v1.0. Simpan di root project dan referensikan di `agent.md` atau `.cursorrules`.

```markdown
# 📜 Source of Truth — LifePlanner SIM v1.0

> **Purpose**: Dokumen ini menetapkan hierarki otoritas, aturan resolusi konflik, dan protokol validasi untuk memastikan konsistensi kode, UI, data, dan behavior sesuai spesifikasi resmi. Semua pengembang dan AI Agent wajib mengacu pada dokumen ini sebelum menghasilkan kode atau keputusan teknis.

---

## 📊 Hierarchy of Truth (Prioritas Mutlak)

Ketika terdapat perbedaan informasi antar dokumen, ikuti urutan prioritas berikut **(dari tertinggi ke terendah)**:

| Prioritas | Dokumen Resmi                          | ID Dokumen             | Cakupan Otoritas                          |
|-----------|----------------------------------------|------------------------|-------------------------------------------|
| 🔴 1      | User Stories & Acceptance Criteria     | `LP-US-AC-2026-001`    | **Behavior, fitur, validasi, DoD, testing** |
| 🟠 2      | PRD & BRD                              | `LP-PRD-BRD-2026-001`  | **Scope, arsitektur, constraint, roadmap**  |
| 🟡 3      | Database Schema & ERD                  | `LP-DB-SCHEMA-2026-001`| **Struktur data, FK, index, ENUM, tipe**    |
| 🟢 4      | TALL Design System                     | `lifeplanner-tall-design-system.html` | **UI tokens, komponen, layout, tipografi** |
| 🔵 5      | AI Agent Guidelines / `agent.md`       | `agent.md`             | **Implementasi teknis, pola kode, Docker**  |

> ✅ **Aturan Emas**: `AC` adalah kebenaran perilaku. `Schema` adalah kebenaran data. `Design System` adalah kebenaran visual. Jika bertabrakan, ikuti prioritas di atas.

---

## ⚖️ Conflict Resolution Protocol

| Skenario Konflik                          | Solusi Resmi                                                                 | Tindakan AI/Developer                          |
|-------------------------------------------|------------------------------------------------------------------------------|------------------------------------------------|
| `AC` vs `PRD`                             | **AC menang** (user behavior > high-level business doc)                      | Implementasi sesuai AC, catat discrepancy      |
| `Schema` vs `AC`/`PRD`                    | **Schema menang** (data integrity non-negotiable)                            | Buat migration sesuai ERD, log gap ke PO       |
| `Design System` vs `AC`/`PRD`             | **AC/PRD menang** (fungsionalitas > estetika)                                | Prioritaskan flow, sesuaikan UI jika memungkinkan |
| `agent.md` vs Dokumen Resmi (1-4)         | **Dokumen Resmi menang**                                                     | Override pattern AI, update `agent.md`         |
| Tidak ada ketentuan di dokumen manapun    | **Konsensus Tim + Dokumentasikan**                                           | Buat PR proposal, minta approval sebelum merge |

---

## 🗂️ Document Scope Mapping (Quick Lookup)

| Pertanyaan Pengembangan                          | Rujuk Dokumen          | Bagian/Section                          |
|--------------------------------------------------|------------------------|-----------------------------------------|
| Apa yang harus dibangun?                         | `LP-US-AC-2026-001`    | User Story + Acceptance Criteria        |
| Kapan fitur dianggap DONE?                       | `LP-US-AC-2026-001`    | Definition of Done (DoD)                |
| Batasan teknis & scope v1.0?                     | `LP-PRD-BRD-2026-001`  | 4. Ruang Lingkup, 7. Constraint & Risiko|
| Teknologi stack & arsitektur?                    | `LP-PRD-BRD-2026-001`  | 10. Arsitektur Sistem                   |
| Nama tabel, kolom, FK, ENUM, index?              | `LP-DB-SCHEMA-2026-001`| 02. Data Dictionary + 04. Rekomendasi Index |
| Warna, font, spacing, komponen UI?               | `lifeplanner-tall-design-system.html` | 02. Sistem Warna, 03. Tipografi, 04. Komponen |
| Pola Livewire, Alpine, Docker, testing?          | `agent.md`             | Backend Patterns, Docker, Coding Standards |

---

## 🤖 AI Agent Directives

Ketika AI menghasilkan kode, desain, atau konfigurasi:

1. **Verifikasi Hierarki**: Selalu cek `LP-US-AC-2026-001` terlebih dahulu untuk behavior, lalu `LP-DB-SCHEMA-2026-001` untuk struktur data.
2. **Reference Explicit**: Tambahkan komentar `// @see LP-US-AC-2026-001 | US-XX AC-Y` pada logika kritis.
3. **Never Assume**: Jika AC tidak mendefinisikan edge case, jangan menebak. Output `⚠️ Ambiguity: [deskripsi] — menunggu konfirmasi PO`.
4. **Schema Immutable**: Jangan ubah nama kolom, tipe, atau FK tanpa persetujuan. Gunakan `migration` untuk perubahan.
5. **Design Token Strict**: Gunakan CSS variables & Tailwind config yang telah didefinisikan. Jangan hardcode hex color.
6. **Fail Fast**: Jika permintaan melanggar constraint `Out-of-Scope v1.0` (multi-user, open banking, AI/ML, export PDF/Excel), tolak dengan referensi `LP-PRD-BRD-2026-001 §4.2`.

---

## 🔄 Versioning & Maintenance

| Versi | Tanggal   | Perubahan                          | Status   |
|-------|-----------|------------------------------------|----------|
| 1.0   | Mei 2026  | Initial release, baseline v1.0     | ✅ Active |

- **Update Protocol**: Perubahan dokumen harus di-commit dengan prefix `docs(sot): ...`
- **Sync Rule**: `agent.md` harus di-update jika ada perubahan di PRD/AC/Schema/Design System
- **Deprecation**: Dokumen lama tidak dihapus, hanya ditandai `DEPRECATED — See vX.X`

---

## ✅ Quick Validation Checklist (Sebelum Commit/PR)

- [ ] Kode memenuhi **semua AC** pada User Story terkait
- [ ] Struktur tabel/kolom sesuai **ERD & Data Dictionary**
- [ ] UI menggunakan **design tokens** (warna, font, spacing, radius)
- [ ] Tidak ada fitur **Out-of-Scope v1.0**
- [ ] Livewire response `< 500ms` (staging)
- [ ] Server-side validation aktif, CSRF/XSS protected
- [ ] Test coverage >70% untuk logika bisnis kritis
- [ ] Responsif di `375px` & `1280px`
- [ ] Komentar `@see` menyertakan ID dokumen & AC

---
*LifePlanner SIM v1.0 | Source of Truth | Mei 2026 | Confidential*
*Referensi: LP-PRD-BRD-2026-001 • LP-US-AC-2026-001 • LP-DB-SCHEMA-2026-001 • Design System HTML*
```

### 💡 Cara Integrasi:
1. Simpan sebagai `SourceOfTruth.md` di root repository.
2. Tambahkan baris ini ke `agent.md`:
   ```markdown
   📜 **Source of Truth**: Always prioritize `SourceOfTruth.md` hierarchy. When in doubt, validate against `LP-US-AC-2026-001` (behavior) → `LP-DB-SCHEMA-2026-001` (data) → Design System (UI).
   ```
3. AI akan otomatis menggunakan dokumen ini sebagai filter validasi sebelum menghasilkan kode, mengurangi hallucination dan drift spesifikasi.