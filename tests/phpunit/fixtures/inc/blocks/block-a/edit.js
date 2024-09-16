import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	return <p { ...useBlockProps() }></p>;
}
