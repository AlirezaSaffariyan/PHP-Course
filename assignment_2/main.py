import sys
import requests
from bs4 import BeautifulSoup


def get_artist_biography(artist_name: str) -> str:
    """
    Scrape biography summary for a given artist

    Args:
        artist_name (str): Name of the artist to search

    Returns:
        str: Biography summary
    """
    # Step 1: Search for the artist
    search_url = f"https://musicbrainz.org/ws/2/artist/?query={artist_name}&fmt=json"
    search_response = requests.get(search_url)
    search_data = search_response.json()

    # Check if artists were found
    if search_data["artists"]:
        # Get the best result
        artist_id = search_data["artists"][0]["id"]

        # Step 2: Get the artist's page
        artist_url = f"https://musicbrainz.org/artist/{artist_id}"
        artist_page_response = requests.get(artist_url)

        # Step 3: Parse the artist's page HTML
        soup = BeautifulSoup(artist_page_response.content, "html.parser")

        # Step 4: Find the biography section
        biography_div = soup.find(
            "div", class_="wikipedia-extract-body wikipedia-extract-collapse"
        )

        # Get the text from p tag
        if biography_div:
            bio = [p.get_text() for p in biography_div.find_all("p")]
            biography = " ".join(bio)
            return biography.strip()
        else:
            return "Biography not available."
    else:
        return "Artist not found."


artist_name = input("Enter the name of the artist: ")

biography = get_artist_biography(artist_name)

print(biography)
