import { Editor } from '@tinymce/tinymce-react';
import 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/models/dom';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';
import 'tinymce/themes/silver';

type Props = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
};

export default function RichTextEditor({ value, onChange, placeholder }: Props) {
    return (
        <div className="overflow-hidden rounded-md border border-slate-300 bg-white focus-within:border-cyan-600 focus-within:ring-1 focus-within:ring-cyan-600">
            <Editor
                value={value}
                onEditorChange={onChange}
                init={{
                    height: 360,
                    menubar: false,
                    skin: false,
                    content_css: false,
                    branding: false,
                    promotion: false,
                    statusbar: false,
                    plugins: 'advlist autolink lists table',
                    toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | table | removeformat',
                    placeholder,
                    content_style: 'body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; } p { margin: 0 0 10px; }',
                }}
            />
        </div>
    );
}
