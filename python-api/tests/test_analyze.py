from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_health():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_analyze_positive():
    payload = {
        "article_id": 1,
        "title": "Pertumbuhan ekonomi Indonesia meningkat pesat",
        "content": "Investasi mengalir dan pasar saham berhasil naik signifikan.",
    }
    response = client.post("/analyze", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["article_id"] == 1
    assert data["sentiment"]["label"] == "positive"
    assert 0.5 <= data["sentiment"]["confidence"] <= 1.0
    assert data["topic"]["label"] in {"ekonomi", "umum"}
    assert isinstance(data["entities"], list)


def test_analyze_negative():
    payload = {
        "article_id": 2,
        "title": "Rupiah anjlok dan bursa krisis",
        "content": "Perusahaan gagal bayar, kerugian besar dan PHK massal.",
    }
    response = client.post("/analyze", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["sentiment"]["label"] == "negative"


def test_analyze_negation_flips_sentiment():
    payload = {
        "article_id": 3,
        "title": "Perusahaan itu tidak sukses tahun ini",
        "content": "Hasil penjualan tidak bagus sama sekali.",
    }
    response = client.post("/analyze", json=payload)
    assert response.status_code == 200
    assert response.json()["sentiment"]["label"] == "negative"


def test_analyze_empty_rejected():
    response = client.post("/analyze", json={"article_id": 4, "title": "", "content": ""})
    assert response.status_code == 422


def test_analyze_extracts_entities():
    payload = {
        "article_id": 5,
        "title": "Presiden Joko Widodo kunjungi Jakarta",
        "content": "Kota Surabaya juga masuk agenda kunjungan kerja.",
    }
    response = client.post("/analyze", json=payload)
    data = response.json()
    types = {entity["type"] for entity in data["entities"]}
    assert "PER" in types
    assert "GPE" in types
