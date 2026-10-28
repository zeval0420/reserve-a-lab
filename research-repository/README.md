# Research repository

Public index of research papers (PDF), at `/research-repository/`.

## Adding a paper

1. Copy the PDF into the folder for its subject:

   | Folder | Subject |
   |---|---|
   | `assets/filipino/` | Filipino |
   | `assets/research/` | Research |
   | `assets/social_science/` | Social Science |

   That alone lists it. With no details, the title comes from the file name and the
   date is the file's date (shown as "Added ...").

2. To give it a proper title, authors, date published, keywords and abstract, add an
   entry to `metadata.json`. The key is the PDF's path from this folder:

   ```json
   {
     "assets/research/water-quality-abra-river.pdf": {
       "title": "Water Quality of the Abra River",
       "authors": ["A. Reyes", "B. Lim"],
       "published": "2023-11",
       "keywords": ["water", "river", "chemistry"],
       "abstract": "One or two sentences."
     }
   }
   ```

   - `published`: `YYYY`, `YYYY-MM` or `YYYY-MM-DD`.
   - `authors` and `keywords`: a list, or one string separated by commas or semicolons.
   - Every field is optional. If you rename or move a PDF, update its key.

## Adding a subject

Create a new folder under `assets/`; it appears as a filter automatically, labelled from
the folder name. To set a different label, add it to `$subjectLabels` in `index.php`.

## Notes

- The list is built on each page load, and search and filters run in the browser, so
  the repository suits up to a few hundred papers.
- Directory listings are blocked by the site's `.htaccess`; a PDF opens only through its
  own link.
- Search matches title, authors, keywords, abstract and file name; accents are ignored.
