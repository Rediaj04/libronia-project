from selenium.webdriver.common.by import By  # Importación necesaria

def clean_text(text):
    """Limpia el texto eliminando espacios y caracteres no deseados."""
    return text.strip() if text else "No disponible"  # Elimina espacios extra y devuelve texto limpio

def split_format_and_pages(pages_format):
    """Separa el formato y número de páginas del libro."""
    if pages_format:
        parts = pages_format.split(",")  # Divide el texto por la coma
        if len(parts) == 2:
            return parts[1].strip(), parts[0].strip()  # Devuelve formato y páginas
    return "Formato no encontrado", "Número de páginas no encontrado"  # Si no se encuentra la información, devuelve valores predeterminados

def get_ratings(driver):
    """Extrae calificaciones y reseñas de un libro."""
    try:
        rating = driver.find_element(By.CSS_SELECTOR, "div.RatingStatistics__rating").text
        ratings_count = driver.find_element(By.CSS_SELECTOR, "span[data-testid='ratingsCount']").text
        reviews_count = driver.find_element(By.CSS_SELECTOR, "span[data-testid='reviewsCount']").text
        return rating, ratings_count, reviews_count  # Devuelve los datos extraídos
    except Exception:
        return "Calificación no encontrada", "No disponible", "No disponible"  # Valores predeterminados en caso de error
