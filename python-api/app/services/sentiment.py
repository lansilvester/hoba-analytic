import re
from collections import Counter

POSITIVE_WORDS = {
    "baik", "bagus", "positif", "sukses", "berhasil", "meningkat", "naik", "tumbuh",
    "membaik", "memuaskan", "gemilang", "cemerlang", "hebat", "unggul", "cuan",
    "untung", "prospektif", "optimis", "cerah", "stabil", "kuat", "andal", "tepat",
    "lancar", "selamat", "aman", "sehat", "ramah", "indah", "cantik", "menarik",
    "populer", "disukai", "dicintai", "dipuji", "menang", "juara", "terbaik",
    "terdepan", "inovatif", "efisien", "menguntungkan", "berkembang", "maju",
    "progres", "bonus", "diskon", "potongan", "hadiah", "rekomendasi", "mantap",
    "mantul", "keren", "top", "kualitas", "berkualitas", "handal", "mengapresiasi", "mendukung", "apresiasi", "dukungan", "pujian",
    "keberhasilan", "pencapaian", "prestasi", "pertumbuhan", "perbaikan", "kenaikan",
}

NEGATIVE_WORDS = {
    "buruk", "jelek", "negatif", "gagal", "rugi", "menurun", "turun", "anjlok",
    "memburuk", "memprihatinkan", "payah", "parah", "kacau", "rusak", "hancur",
    "bencana", "korban", "meninggal", "tewas", "mati", "kematian", "kecelakaan",
    "kriminal", "pencurian", "korupsi", "suap", "penipuan", "bohong", "curang",
    "dosa", "salah", "keliru", "miskin", "kemiskinan", "pengangguran", "krisis",
    "inflasi", "resesi", "defisit", "utang", "kendala", "hambatan", "masalah",
    "gejolak", "konflik", "perang", "kekerasan", "kekacauan", "kerusuhan", "demo",
    "mogok", "protes", "unjuk rasa", "dituduh", "tersangka", "terdakwa", "pidana",
    "penjara", "tahanan", "sanksi", "cemas", "khawatir", "takut", "cemburu", "iri",
    "benci", "marah", "frustrasi", "kecewa", "sedih", "kekecewaan", "keluhan",
    "komplain", "tuntutan", "ancaman", "bahaya", "risiko", "paham negatif",
    "kritik", "mengecam", "mengutuk", "melarang", "memblokir", "larangan",
    "menurunnya", "penurunan", "kerugian", "kebangkrutan", "penutupan", "PHK",
    "pemecatan", "cuti", "sakit", "penyakit", "wabah", "pandemi", "bencana alam",
    "banjir", "gempa", "longsor", "kebakaran", "asap", "polusi", "pencemaran",
}

NEGATIONS = {"tidak", "bukan", "belum", "jangan", "tak", "kurang", "enggak", "ga", "nggak"}

TOPIC_KEYWORDS = {
    "politik": {
        "presiden", "pemilu", "pilpres", "partai", "kpu", "debat", "kandidat",
        "wakil presiden", "menteri", "kabinet", "dpr", "parlemen", "koalisi",
        "jokowi", "prabowo", "anies", "ganjar", "legislatif", "oligarki",
    },
    "ekonomi": {
        "ekonomi", "rupiah", "dolar", "pasar saham", "ihsg", "bumn", "gdp",
        "pdb", "inflasi", "subsidi", "pajak", "ekspor", "impor", "investasi",
        "bisnis", "perusahaan", "bank", "perbankan", "fintech", "startup",
        "umkm", "lapangan kerja", "tenaga kerja", "harga", "komoditas",
    },
    "teknologi": {
        "teknologi", "digital", "ai", "kecerdasan buatan", "robot", "software",
        "aplikasi", "platform", "startup", "internet", "data", "cyber", "siber",
        "keamanan siber", "server", "gadget", "smartphone", "game", "nft",
        "cryptocurrency", "bitcoin", "5g", "cloud", "komputer",
    },
    "olahraga": {
        "sepak bola", "liga", "piala", "timnas", "badminton", "bulu tangkis",
        "ganda", "atlet", "pertandingan", "kemenangan", "gol", "skor", "cabor",
        "olimpiade", "sea games", "voli", "basket",
    },
    "kesehatan": {
        "kesehatan", "rumah sakit", "dokter", "vaksin", "obat", "pasien",
        "pandemi", "wabah", "penyakit", "stunting", "gizi", "imunisasi", "bpjs",
        "fasilitas kesehatan", "puskesmas", "virus", "covid", "klinik",
    },
    "pendidikan": {
        "pendidikan", "sekolah", "universitas", "kampus", "guru", "mahasiswa",
        "pelajar", "ujian", "kurikulum", "beasiswa", "perguruan tinggi", "kemdikbud",
        "siswa", "asn guru", "pppk", "literasi",
    },
    "hukum": {
        "hukum", "pengadilan", "vonis", "penjara", "kpk", "polisi", "kejaksaan",
        "korupsi", "kasus", "putusan", "dakwaan", "tersangka", "perkara", "advokat",
    },
    "lingkungan": {
        "lingkungan", "iklim", "polusi", "sampah", "deforestasi", "hutan",
        "karbon", "energi terbarukan", "emisi", "reboisasi", "sungai", "laut",
        "bencana alam", "banjir", "gempa", "kebakaran hutan",
    },
    "hiburan": {
        "film", "musik", "konser", "artis", "selebriti", "drama", "sinetron",
        "box office", "album", "penghargaan", "festival", "streaming", "viral",
    },
    "internasional": {
        "internasional", "asing", "amerika", "china", "russia", "ukraina",
        "pbb", "nato", "perang", "konflik", "dunia", "global", "g20",
    },
}

CITY_GAZETTEER = {
    "jakarta", "surabaya", "bandung", "medan", "semarang", "makassar", "palembang",
    "yogyakarta", "depok", "tangerang", "bekasi", "bogor", "malang", "padang",
    "denpasar", "samarinda", "banjarmasin", "pontianak", "manado", "aceh",
    "indonesia", "jawa", "sumatera", "kalimantan", "sulawesi", "papua",
    "jabodetabek", "solo", "surakarta", "pekanbaru", "balikpapan",
}

WORD_RE = re.compile(r"[A-Za-z]+")
NEGATION_WINDOW = 3


def _normalize(text: str) -> list[str]:
    text = re.sub(r"[\u2018\u2019\u201c\u201d]", "'", text.lower())
    return WORD_RE.findall(text)


def analyze_sentiment(text: str) -> tuple[str, float]:
    words = _normalize(text)
    if not words:
        return "neutral", 0.5

    positive = 0
    negative = 0
    for i, word in enumerate(words):
        if word in NEGATIONS:
            continue
        window = words[max(0, i - NEGATION_WINDOW):i]
        negated = any(w in NEGATIONS for w in window[-2:])
        if word in POSITIVE_WORDS:
            if negated:
                negative += 1
            else:
                positive += 1
        elif word in NEGATIVE_WORDS:
            if negated:
                positive += 1
            else:
                negative += 1

    total = positive + negative
    if total == 0:
        return "neutral", 0.5

    score = (positive - negative) / total
    if score > 0.15:
        label = "positive"
    elif score < -0.15:
        label = "negative"
    else:
        label = "neutral"

    confidence = round(min(0.5 + abs(score) * 0.5, 0.99), 3)
    return label, confidence


def detect_topic(text: str) -> str:
    lowered = text.lower()
    counts: Counter = Counter()
    for topic, keywords in TOPIC_KEYWORDS.items():
        for keyword in keywords:
            if keyword in lowered:
                counts[topic] += 1
    if not counts:
        return "umum"
    return counts.most_common(1)[0][0]


TOPIC_CLUSTER_IDS = {topic: idx for idx, topic in enumerate(sorted(TOPIC_KEYWORDS) + ["umum"])}


def extract_entities(text: str) -> list[dict]:
    entities: list[dict] = []
    seen: set[tuple[str, str]] = set()

    for city in CITY_GAZETTEER:
        pattern = re.compile(r"\b" + re.escape(city) + r"\b", re.IGNORECASE)
        if pattern.search(text):
            key = ("GPE", city.lower())
            if key not in seen:
                seen.add(key)
                entities.append({"type": "GPE", "text": city})

    person_pattern = re.compile(r"\b[A-Z][a-z]{2,}(?:\s+[A-Z][a-z]{2,}){1,2}\b")
    for match in person_pattern.findall(text):
        key = ("PER", match.lower())
        if key not in seen:
            seen.add(key)
            entities.append({"type": "PER", "text": match})

    return entities[:10]
