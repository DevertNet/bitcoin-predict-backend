# bitcoin-predict-backend

Fetch news articles and rate them with the help of chatgpt. Provide a api for bitcoin-predict-frontend.

# Todo

- Fetch more news
- Rate with chatgpt
- Rate with popularity
- Maybe late: fetch content from news sites; currently only the title will be rated

# Rating Method for PredictRatingV1

- Rate is between -10 and 10; -10 is bad; 10 is good
- Ask chatgpt for rate the title: positive = -5, negative=0 and neutral=5
- Add popularity based on source = Push rating to -10 or 10

# Usage

Fetch news into database:
`./bin/console app:fetch-news`

Then rate the news with chatgpt:
```./bin/console app:rate-news`
