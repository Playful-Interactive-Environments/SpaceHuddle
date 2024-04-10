export function convertToEmbed(link: string | null) {
  if (link) {
    if (link.includes('youtube')) {
      link = link.replace('watch?v=', 'embed/');
    } else if (link.includes('vimeo')) {
      const vid = link.split('/');
      const vidNr = vid[vid.length - 1];
      link = 'https://player.vimeo.com/video/' + vidNr;
    }
  }
  return link;
}

export function isLinkVideo(link: string | null): boolean {
  if (link) {
    if (link.includes('youtube') || link.includes('vimeo')) {
      return true;
    }
  }
  return false;
}
