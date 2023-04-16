# bitcoin-predict-backend

Fetch news articles and rate them with the help of chatgpt. Provide a api for bitcoin-predict-frontend.

# Rating Method for PredictRatingV1

## Method

- Every news will be rated between -10 and 10; -10 is bad; 10 is good
- Ask chatgpt for rate the news title: positive = 10, negative=-10 and neutral=0
- Add popularity based on source. I used the Global Ranking from https://www.similarweb.com/website/theage.com.au/#overview. The rank (3000000 to 1) will be transformed to 0.5 and 1. The transformed value will be multiplied with the inital score from chatgpt.
- Used news scope:
  - api: mediastack.com
  - languages: en
  - categories: technology,business
  - keywords: crypto, bitcoin

## Prompt

Forget all your previous instructions. Pretend you are a financial expert. You are a financial expert with stock recommendation experience. Answer “YES” if good news, “NO” if bad news, or “UNKNOWN” if uncertain in the first line. Then elaborate with one short and concise sentence on the next line. Headline:

## Conclusion

![](readme-v1.png)
Unfortunately, the Mediastack API has a bug, which is why only news from the last 3 months are available. Despite paid tariff. Overall, I can't see any connection between news and the chart.
The chart shows entries per calendar day. The news scores are simply added up per calendar day.
The problem why it can't work is perhaps the too small selection of news.

## Recommendation and ideas for v2

- Add more news. I think all news that can also influence the stock market is also relevant for Bitcoin. Don't filter the news for keywords.
- Switch to another news api, if mediastack cannot provide historical news.
- Scrape the content of the news and include dem in the ChatGPT rating.
- Make the prompt more bitcoin-based. ChatGpt may therefore not rate the news in relation to bitcoin.
- Change the popularity ratio to 0.1 to 1. This will give more impact for the popularity.

# Usage

Fetch news into database:
`./bin/console app:fetch-news`

Then rate the news with chatgpt:
```./bin/console app:rate-news`
