import requests
from bs4 import BeautifulSoup
import re


def search_artist_biography(artist_name: str) -> dict[str, str]:
    """
    Scrape biography summary for a given artist

    Args:
        artist_name (str): Name of the artist to search

    Returns:
        dict: Biography summary information
    """
    encoded_name = artist_name.replace(" ", "_")

    url = f"https://en.wikipedia.org/wiki/{encoded_name}"

    try:
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        }
        response = requests.get(url, headers=headers)

        if response.status_code == 200:
            soup = BeautifulSoup(response.text, "html.parser")

            biography = {}

            summary_paragraphs = soup.select(".mw-parser-output > p")
            summary_text = " ".join(
                [p.get_text().strip() for p in summary_paragraphs[:2]]
            )

            summary_text = re.sub(r"\[.*?\]", "", summary_text)
            summary_text = re.sub(r"\s+", " ", summary_text)

            biography["summary"] = summary_text

            infobox = soup.select_one(".infobox")
            if infobox:
                birth_row = infobox.find("th", string=re.compile(r"Born"))
                if birth_row:
                    biography["birth_details"] = (
                        birth_row.find_next("td").get_text().strip()
                    )

            return biography

        else:
            print(f"Failed to retrieve page. Status code: {response.status_code}")
            return None

    except requests.RequestException as e:
        print(f"Error occurred: {e}")
        return None


artist_name = input("Enter the name of the artist: ")
artist_name = " ".join(map(lambda x: x.capitalize(), artist_name.split(" ")))

biography = search_artist_biography(artist_name)

if biography:
    print("\n--- Artist Biography Summary ---")

    if "summary" in biography:
        print("\nSummary:")
        print(biography["summary"].strip())
else:
    print("Could not find biography information.")
