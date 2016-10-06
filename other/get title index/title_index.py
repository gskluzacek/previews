import requests
from lxml import html
import re


class TitleIndex:
    def __init__(self, title_id, src_html=None):
        if title_id != 0:
            self.title_id = title_id
            self.url = 'http://www.comicbookdb.com/title.php?ID=' + str(title_id)
            page = requests.get(self.url)
            self.html = page.content
        else:
            self.title_id = None
            self.url = None
            self.html = src_html

        self.tree = html.fromstring(self.html)

        # gets all the <tr> & <tbody> tags with the issue data
        tags = self.tree.xpath("//a[@class='page_link'][text()='Issue']/ancestor::table[1]/child::*")[1:]

        # collects all the <tr> tags into a 1 level array and removes <tbody> tags
        self.tr_tags = []
        for tag in tags:
            if tag.tag == 'tr':
                self.tr_tags.append(tag)
            else:
                tbody = tag
                for tr_tag in tbody:
                    self.tr_tags.append(tr_tag)

        # create 4 columns that correspond to the columns containing isuue data
        # each column contains the <td> tag corresponding to the index row
        self.cols = [[], [], [], []]
        for tr_tag in self.tr_tags:
            for i in range(0, 8, 2):
                self.cols[(i // 2)].append(tr_tag[i])

        self.issue_num = ''
        self.issue_title = ''
        self.raw_issue_num = ''
        self.story_arc = ''
        self.story_arc_id = ''

        self.index = []

    def get_index(self):
        for i in range(0, len(self.tr_tags)):
            self.index.append(dict(
                issue_id=self.get_issue_id_text(i),
                issue_num=self.get_issue_num_text(i),
                issue_title=self.get_issue_title_text(i),
                var_desc=self.get_variant_desc_text(i),
                cover_date=self.get_cover_date_text(i),
                story_arc=self.get_story_arc_text(i),
                story_arc_id=self.get_story_arc_id_text(i)
            ))

    def get_issue_num_text(self, n):
        e = self.cols[0][n]

        if len(e) == 0:
            self.raw_issue_num = ''
            return self.issue_num
        elif len(e) == 2 or len(e) == 3:
            self.raw_issue_num = e[len(e) - 2].text
            if len(self.raw_issue_num) != 0:
                self.issue_num = self.raw_issue_num
            if len(self.raw_issue_num) != 0 or len(self.issue_num) != 0:
                return self.issue_num
            else:
                raise Exception(
                    'ERROR: get_issue_num_text() - input element: `{0:s}` has a blank `issue number`'.format(
                        html.tostring(e)))
        else:
            raise Exception(
                'ERROR: get_issue_num_text() - input element: `{0:s}` with `{1:s}` child nodes, does not have a ' +
                'valid number of child nodes (0, 2 or 3)'.format(html.tostring(e), len(e)))

    def get_issue_title_text(self, n):
        e = self.cols[1][n]
        if len(e) == 2:
            if len(self.raw_issue_num) != 0:
                tmp_title = e[0].text
                if tmp_title is None:
                    self.issue_title = ''
                else:
                    self.issue_title = tmp_title
            return self.issue_title
        else:
            raise Exception(
                'ERROR: get_issue_title_text() - input element: `{0:s}` with `{1:s}` child nodes, does not have a ' +
                'valid number of child nodes (2)'.format(html.tostring(e), len(e)))

    def get_variant_desc_text(self, n):
        e = self.cols[1][n]
        if len(e) == 2:
            if len(self.raw_issue_num) != 0:
                f = self.cols[0][n + 1]  # get the 1st col (issue num) for the next record
                if len(f) == 0:  # if the <td> tag has 0 child nodes, it has a blank issue num
                    return 'STD-VARIANT'  # which means the next issue is a variant, therefore this is a STD-VARIANT
                elif len(f) == 2 or len(f) == 3:  # else if the <td> tag has 2 or 3 child nodes, it has an issue num
                    return 'NON-VARIANT'  # which means the next is is not a variant, therefore this is
                else:  # a NON-VARIANT
                    raise Exception(  # if not 0, 2 or 3, then it is an exception
                                      'ERROR: get_variant_desc_text() - NEXT input element: `{0:s}` with `{1:s}` child nodes, does ' +
                                      'not have a valid number of child nodes (0, 2 or 3)'.format(html.tostring(f),
                                                                                                  len(f)))
            else:
                var_desc = e[0].text
                if var_desc is not None:
                    return var_desc
                else:
                    raise Exception(
                        'ERROR: get_variant_desc_text() - input element: `{0:s}` has a blank `variant ' +
                        'description`'.format(html.tostring(e)))
        else:
            raise Exception(
                'ERROR: get_variant_desc_text() - input element: `{0:s}` with `{1:s}` child nodes, does not have a ' +
                'valid number of child nodes (2)'.format(html.tostring(e), len(e)))

    def get_issue_id_text(self, n):
        ptrn_issue_id = re.compile(r'issue.php\?ID=(\d*)')

        e = self.cols[1][n]

        if len(e) == 2:
            href = e[0].get('href')
            if href is not None:
                match = ptrn_issue_id.match(href)
                if match is not None:
                    issue_id = match.group(1)
                    if len(issue_id) != 0:
                        return issue_id
                    else:
                        raise Exception(
                            'ERROR: get_issue_id_text() - input element: `{0:s}` has a blank ' +
                            '`issue id`'.format(html.tostring(e)))
                else:
                    raise Exception(
                        'ERROR: get_issue_id_text() - input element: `{0:s}` with a href value of `{1:s}` does not ' +
                        'have a href value (starts with `issue.php?ID=`)'.format(html.tostring(e), href))
            else:
                raise Exception(
                    'ERROR: get_issue_id_text() - input element: `{0:s}` does not have a `href` ' +
                    'attribute'.format(html.tostring(e)))
        else:
            raise Exception(
                'ERROR: get_issue_id_text() - input element: `{0:s}` with `{1:s}` child nodes, does not have a ' +
                'valid number of child nodes (2)'.format(html.tostring(e), len(e)))

    def get_story_arc_text(self, n):
        e = self.cols[2][n]

        if len(e) == 0:
            return self.story_arc
        elif len(e) == 2:
            story_arc = e[0].text
            if story_arc is not None:
                self.story_arc = story_arc
            else:
                self.story_arc = ''
            return self.story_arc
        else:
            raise Exception(
                'ERROR: get_story_arc_text() - input element: `{0:s}` with `{1:s}` child nodes, does not have a ' +
                'valid number of child nodes (0 or 2)'.format(html.tostring(e), len(e)))


    def get_story_arc_id_text(self, n):
        ptrn_story_arc_id = re.compile(r'storyarc.php\?ID=(\d*)')

        e = self.cols[2][n]

        if len(e) == 0:
            return self.story_arc_id
        elif len(e) == 2:
            href = e[0].get('href')
            if href is not None:
                match = ptrn_story_arc_id.match(href)
                if match is not None:
                    self.story_arc_id = match.group(1)
                    return self.story_arc_id
                else:
                    raise Exception(
                        'ERROR: get_story_arc_id_text() - input element: `{0:s}` with a href value of `{1:s}` does ' +
                        'not have a href value (starts with `storyarc.php?ID=`)'.format(html.tostring(e), href))
            else:
                raise Exception(
                    'ERROR: get_story_arc_id_text() - input element: `{0:s}` does not have a `href` ' +
                    'attribute'.format(html.tostring(e)))
        else:
            raise Exception(
                'ERROR: get_story_arc_id_text() - input element: `{0:s}` with `{1:s}` child nodes, does not have a ' +
                'valid number of child nodes (0 or 2)'.format(html.tostring(e), len(e)))


    def get_cover_date_text(self, n):
        e = self.cols[3][n]

        if len(e) == 0 or len(e) == 1:
            cover_date = e.text
            if cover_date is not None:
                return cover_date
            else:
                raise Exception(
                    'ERROR: get_cover_date_text() - input element: `{0:s}` has a blank ' +
                    '`cover date`'.format(html.tostring(e)))
        else:
            raise Exception(
                'ERROR: get_cover_date_text() - input element: `{0:s}` with `{1:s}` child nodes, does not have a ' +
                'valid number of child nodes (0 or 1)'.format(html.tostring(e), len(e)))


# file = '/Users/gskluzacek/Documents/Development/REPOS/previews/old_code/Preview Parsing/sample.html'
file = "../../old_code/Preview Parsing/sample.html"
f = open(file, 'r', -1, 'windows-1251')
src_html = f.read()
f.close()

index = TitleIndex(0, src_html)

index.get_index()

for line in index.index:
    print("`{0:s}` `{1:s}` `{2:s}` `{3:s}` `{4:s}` `{5:s}` `{6:s}`".format(
            line['issue_id'], line['issue_num'], line['issue_title'], line['var_desc'], line['cover_date'],
            line['story_arc'], line['story_arc_id']))

print("DONE")
