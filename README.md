TNT Product Video for WooCommerce

## 📄 Περιγραφή

Το **TNT Product Video** είναι ένα ελαφρύ WordPress plugin που αντικαθιστά τη βασική εικόνα προϊόντος στο WooCommerce με YouTube βίντεο, όταν υπάρχει σχετικό URL.

Το plugin:
- εμφανίζει βίντεο μόνο στα προϊόντα που έχουν έγκυρο YouTube URL,
- αφήνει τα υπόλοιπα προϊόντα ανεπηρέαστα,
- χρησιμοποιεί native hooks του WooCommerce για πιο καθαρή ενσωμάτωση.

## ✨ Λειτουργίες

- Προσθήκη custom πεδίου **Video URL** στο admin κάθε προϊόντος.
- Προστασία αποθήκευσης με nonce/capability checks.
- Ανίχνευση YouTube URL σε μορφές:
  - `watch?v=...`
  - `youtu.be/...`
  - `embed/...`
  - `shorts/...`
- Αυτόματη μετατροπή σε embed μορφή.
- Εμφάνιση mini preview μέσα στο meta box του προϊόντος.
- Αντικατάσταση της gallery εικόνων με βίντεο μόνο όταν το URL είναι έγκυρο.
- Νέα στήλη στη λίστα προϊόντων (**Product Video**) με ένδειξη κατάστασης.

## ⚡ Εγκατάσταση

1. Κατέβασε/κλωνοποίησε το repository.
2. Αντέγραψε τον φάκελο `TNT-Product-Video-for-WooCommerce` στο:
   - `wp-content/plugins/`
3. Από το WordPress admin πήγαινε:
   - **Plugins → TNT Product Video for WooCommerce → Activate**
4. Άνοιξε ένα προϊόν και συμπλήρωσε το πεδίο **🎥 Video URL (YouTube)**.

## 🔹 Υποστηριζόμενα YouTube URLs

| Είσοδος | Μετατρέπεται σε |
|---|---|
| `https://www.youtube.com/watch?v=abc123` | `https://www.youtube.com/embed/abc123` |
| `https://youtu.be/abc123` | `https://www.youtube.com/embed/abc123` |
| `https://www.youtube.com/embed/abc123` | Χωρίς αλλαγή |
| `https://www.youtube.com/shorts/abc123` | `https://www.youtube.com/embed/abc123` |

## 📁 Αρχεία Plugin

```text
tnt-product-video/
├── assets/
│   └── css/
│       └── tnt-product-video.css
├── tnt-product-video.php
├── README.md
└── LICENSE
```

## 🚀 Συγγραφείς

Lamprian, Fene, Nikolakith

## 🌐 Άδεια χρήσης

MIT License: https://opensource.org/licenses/MIT
